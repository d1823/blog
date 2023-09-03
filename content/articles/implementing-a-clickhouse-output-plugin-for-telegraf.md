[//]: # (TITLE: Implementing a ClickHouse output plugin for Telegraf in Go)
[//]: # (DESCRIPTION: A process of implementing a Go-based ClickHouse output plugin for Telegraf, along with the surprises and pitfalls.)
[//]: # (DATE: 2023-08-27)
[//]: # (TAGS: telegraf, clickhouse, go)

Telegraf describes itself as a server-based agent for collecting and sending all metrics and events from databases, systems & IoT sensors. ClickHouse, on the other hand, is a column-oriented open-source database designed for real-time apps and analytics.

One of the clients I worked for has been using ClickHouse to store all kinds of telemetry captured throughout the infrastructure. Initially, they used InfluxDB as the storage, but as the infrastructure grew, they quickly found out they needed a more powerful solution than InfluxDB, and ClickHouse became a natural choice.

Its extreme performance and a collection of high-quality integrations with various systems have placed it at the very top of the ranking while they were evaluating the choices. One caveat, at the time, was the lack of any kind of mature integration between two of these systems. 

### Figuring out the issues

Although Telegraf supported a lot of storage engines (called output plugins), it lacked **proper** support for ClickHouse. Well, to be completely honest, ClickHouse was supported through their SQL output plugin, but it was built on top of the Go's [database/sql](https://pkg.go.dev/database/sql) API and suffered from a lot of shortcomings that affected its performance:

- it doesn't support using the [ch-go](https://github.com/ClickHouse/ch-go) package designed for very fast data block streaming with low resources overhead
- it doesn't support [asynchronous inserts](https://clickhouse.com/docs/en/optimize/asynchronous-inserts) to lower the number of disk writes on the database side
- it doesn't support [metrics batching so every metric results in a insert statement](https://github.com/influxdata/telegraf/blob/v1.27.4/plugins/outputs/sql/sql.go#L238-L256)

All of that affected the memory usage of the Telegraf itself, and generated a huge number of disk-writes on the ClickHouse side, affecting the performance of the whole server.

That setup has been running in the production for a while, but eventually, a decision was made to take the matter into our own hands and implement a dedicated output plugin that would use some of ClickHouse-specific features, lowering the overall footprint on both sides of the integration.

### Writing the plugin

The pretty cool thing about Telegraf in itself is that it's heavily extendable. Every source and every storage engine integration is implemented as a plugin. As such, it can be brought into the project, becoming part of the source tree, or be implemented as an external plugin, integrated with telegraf through its [execd wrapper](https://github.com/influxdata/telegraf/blob/master/plugins/inputs/execd/README.md).

The communication is performed over the stdin/stdout as metrics are serialized into [Influx Line Protocol](https://docs.influxdata.com/influxdb/v2.7/reference/syntax/line-protocol/). For Go developers, there's a convenient [execd shim package](https://github.com/influxdata/telegraf/blob/master/plugins/common/shim/README.md) that can be used to bootstrap the development of the desired integration. It takes care of reading & writing the metrics using the native protocol, while the plugin itself needs to fulfill the following simplified interface:

```go
type Output interface {
  SampleConfig() string
  Connect() error
  Close() error
  Write(metrics []Metric) error
}
```

Unfortunately, at the time of writing this article, the way the execd shim handles the incoming metrics prevents the implementations from processing them in any other way than one-by-one - a [bug known to the maintainers, but yet to be resolved](https://github.com/influxdata/telegraf/issues/11902), caused by [this simple loop](https://github.com/influxdata/telegraf/blob/master/plugins/common/shim/output.go#L41-L50). In my case, the most obvious workaround involved a timer-based buffer that called the output plugin with a list of accumulated metrics - something along these lines:

```go
func (s *customShim) RunOutput() error {
  // omitted for brevity

  ch := make(chan telegraf.Metric, bufferSize)

  wg := &sync.WaitGroup{}
  wg.Add(1)

  go func() {
    defer wg.Done()

    t := time.NewTicker(s.d)
    defer t.Stop()

    metrics := make([]telegraf.Metric, 0, bufferSize)

    defer func() {
      if err = s.Output.Write(metrics); err != nil {
        fmt.Fprintf(os.Stderr, "Failed to write metrics: %s\n", err)
      }
    }()

  loop:
    for {
      select {
      case m, ok := <-ch:
        if !ok {
          break loop
        }
        metrics = append(metrics, m)
      case <-t.C:
        if err = s.Output.Write(metrics); err != nil {
          fmt.Fprintf(os.Stderr, "Failed to write metrics: %s\n", err)
        }
        metrics = metrics[:0]
      }
    }
  }()

  scanner := bufio.NewScanner(os.Stdin)
  for scanner.Scan() {
    m, err := parser.ParseLine(scanner.Text())
    if err != nil {
      fmt.Fprintf(os.Stderr, "Failed to parse metric: %s\n", err)
      continue
    }

    ch <- m
  }

  close(ch)
  wg.Wait()

  return nil
}
```

The original intention of the authors behind the Telegraf project was to use it with their main product - InfluxDB, a time-series database where data is saved as a key-value set of fields and tags. One of the side-effects is the fact that it does not require an explicit schema, which is ideal for Telegraf, because metrics generated by input plugins are not bound to any schema either. That's not the case for any kind of SQL database, ClickHouse included. Creating the schema and keeping it up to date has to be part of the feature set.

Fortunately, doing it correctly was not a difficult task. To make things simple, each type of metric will be stored in a separate table. Given the interface of the output plugin, a quick scan over the given slice of metrics was enough to gather enough information about fields and tags that had to be supported by the corresponding table as columns. In the meantime, we also had to look at each column's value. A simple mapping from native Go types to their ClickHouse counterparts was enough to get this going.

Before constructing the insert query, we can use all that information to either create or alter the relevant table. The only thing I haven't decided to pursue was altering the existing columns - doing so could lead to a potential data loss and as such would be difficult to implement safely. To be honest, I'm not sure it's even supported by the InfluxDB, let alone any other output plugin.

```go
func (s *Clickhouse) Write(metrics []telegraf.Metric) error {
  tableCols := make(map[string]map[string]Datatype)
  tableColValues := make(map[string][]map[string]interface{})

  for _, metric := range metrics {
    batch := make(map[string]interface{})

    if tableCols[metric.Name()] == nil {
      tableCols[metric.Name()] = make(map[string]Datatype, len(metric.Tags())+len(metric.FieldList())+1)
    }

    if s.TimestampColumn != "" {
      tableCols[metric.Name()][s.TimestampColumn] = resolveDatatype(metric.Time())
      batch[s.TimestampColumn] = metric.Time()
    }

    for column, value := range metric.Tags() {
      tableCols[metric.Name()][column] = resolveDatatype(value)
      batch[column] = value
    }

    for column, value := range metric.Fields() {
      tableCols[metric.Name()][column] = resolveDatatype(value)
      batch[column] = value
    }

    tableColValues[metric.Name()] = append(tableColValues[metric.Name()], batch)
  }

  // omitted for brevity
}
```

```go
func resolveDatatype(value interface{}) Datatype {
	var datatype Datatype

	switch value.(type) {
	case int64:
		datatype = Integer
	case uint64:
		datatype = Unsigned
	case float64:
		datatype = Real
	case string:
		datatype = Text
	case bool:
		datatype = Bool
	case time.Time:
		datatype = Timestamp
	default:
		datatype = Default
	}
	return datatype
}
```

As for actually writing the metrics to the database, I used the [Asynchronous Inserts](https://clickhouse.com/docs/en/optimize/asynchronous-inserts) functionality. In short, it enables ClickHouse to memory-buffer the inserted values before writing them to disk. It takes care of the issue created when it has to handle a lot of small and frequent inserts. To increase the performance of the whole solution, I've made sure each batch of metrics converts into a single insert query per table. That way, even if I decide the async writes should no longer be enabled, the plugin will still execute - at most - a single insertion per table per second.

### Let's sum it up!

It took me around two days to implement and deploy this solution to production. I've lowered the memory footprint on the Telegraf side, and solved the excessive disk writes issue on the ClickHouse side. I've also learned a bunch about Telegraf. My main takeaway from this little project is this - don't be afraid of writing integrations yourself, because it might actually be worth it.
