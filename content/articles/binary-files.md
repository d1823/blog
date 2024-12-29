[//]: # (TITLE: Binary files, creating your own file format)
[//]: # (DESCRIPTION: Creating a very basic binary file format.)
[//]: # (DATE: 2012-10-21)
[//]: # (TAGS: c, cpp, c++, good old times)

> I'm republishing this article to preserve memories—like keeping old photos.
>
> This content comes from some of my older blogs which I lost access to long
> ago, but are miraculously still online. Given their age (and how young &
> inexperienced I was when I wrote them), these articles aren’t meant to be
> learning resources, but more of a look back at my past work for myself.

<br/>
*At first, to understand this post, you have to know basic binary operations and a bit of C++ language. You do? Cool. Let's roll.*

## What?
That kind of file is not designed for being read by text-editors. Of course,
there's no problem with it. A bit advanced editors would open binary file but
believe me, you won't see anything useful. Not because it's encrypted or
protected by some kind of password. It's just, they are nothing more than a
sequence of bytes, written one by one, most of the time grouped in blocks. How
they are grouped, well, that depends of the file format.

## Why?
Mostly, because of it's efficiency. If you have a scheme of the file format,
you can write an application that will read the file and get it's information.
They are much lighter than their text represented alternatives. String occupy
much more space in the memory than lean numbers, and the information you want
to store is not always a text. Sometimes it's a content of an archive or an
*.exe file. That kind of data was not meant to be read by people, but by
machines, so it would be a big misunderstanding if we would even try to store
that kind of information as a string.

## How?
Well, there are two ways of creating your own file format. The first one is to
grab some data and write a file with it, using a binary mode. It's fast, it's
efficient, but at the same time, very chaotic and problematic. Reasons?

- If you have more of those files, how would you know that what you are trying
  to read is your file format?
- How will you distinguish different version of your file format? Application
  using older version may not be able to read your data properly, leading to
  major problems.
- Writing a binary data without proper informations describing your file format
  will mislead other applications, which may to lead to data corruption.

The second way of creating a binary file format is to break your format into
sections. The first one, should be file format header, containing unique
signature and proper version of your file format. Such a simple action and it's
solving all of the problems I mentioned before. File format signature prevents
mistakes while loading wrong files. Version of your file format lets you
control the different kinds of files you'll try to load into you application.
Depending on kind of information you want to store, you'll have to create more
sections containing important data.


Example of an application writing and reading in binary mode.

```cpp
#include <iostream>
#include <fstream>

struct FHeader
{
    char signature;
    int version;
};

struct FData
{
    int number;
    char sign;
    bool state;
};

struct FContainer
{
    FHeader header;
    FData data;
};

int main()
{
    FContainer in_container;
    FContainer of_container;
    of_container.header.signature = 'R';
    of_container.header.version = 0x1;
    of_container.data.number = 5;
    of_container.data.sign = 'A';
    of_container.data.state = false;

    std::ofstream output_stream("file.bin", std::ios::binary);
    output_stream.write(reinterpret_cast<const char*>(&of_container), sizeof(of_container));
    output_stream.close();

    std::ifstream input_stream("file.bin", std::ios::binary);
    input_stream.read(reinterpret_cast<char*>(&in_container), sizeof(in_container));
    input_stream.close();

    std::cout << in_container.header.signature << std::endl;
    std::cout << in_container.header.version << std::endl;
    std::cout << in_container.data.number << std::endl;
    std::cout << in_container.data.sign << std::endl;
    std::cout << in_container.data.state << std::endl;
}
```
