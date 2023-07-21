[//]: # (TITLE: Thoughts on exceptions as values)
[//]: # (DESCRIPTION: Thoughts on traditional exception-based error handling in comparison to Go's error-aware multi-value function return statements)
[//]: # (DATE: 2023-03-26)
[//]: # (TAGS: php, go, errors, error handling, short thoughts)

I've always had a problem with exception handling in PHP. From the very beginning of my career, I've been told to and
read that throwing an exception is dedicated for very exceptional situations (ex. when the argument doesn't meet some business requirements,
or we receive an error from a call to a third-party service). Unfortunately, to me, that explanation was never enough, especially given that
the same sources never really shown me what to do when an error is part of a result but at the same its representation requires
a bit more than a simple `bool` type to indicate some meaning. I've always felt that some errors should be treated as results,
not as some exceptional cases that may, or may not be handled by the caller.

At least in the PHP world, there's no language-level indication an exception can be thrown. If the piece of code you're calling
isn't annotated with a set of `@throws` describing the exact exception you might get, you're pretty much blind and most developers
will happily ignore that unknown scenario. In the end, what's the worst that could happen? It's going to be caught by a framework-level
exception handler, right? Maybe. But leaving errors unhandled is never a good idea.

To me, building a web application in Go seems to be related to the same kind of issues one might have while developing with PHP,
and error handling is definitely one of them. Despite that, I'd be lying if I said that it didn't feel slightly different. In Go,
error-handling is not some invisible side effect like it is in PHP. You don't need to rely on comments to tell you a method might
throw, or worry that the execution flow will suddenly be interrupted by an exception thrown ten layers down the stack, unless
it's a panic. Even these situations are far and few between because of how panics are fundamentally treated [by the language](https://go.dev/ref/spec#Run_time_panics) and developers.
Making a function panic, means halting the normal execution flow and bypassing the typical error handling to let outer scopes
deal with the issue, or let the program exit. Although it might not seem that much different than exception mechanism found in other
languages, the whole error handling aspect differs in that there's an actual alternative that's dedicated to handling error conditions.

This whole thing means that, suddenly, a constraint violation emitted by a DB call is no longer invisible and needs to be
explicitly ignored if that's the developer's decision. It also means that a function can have multiple results where an error
is one of them, and is no longer treated as a side effect that has to be consciously searched for. I've got to say that I really like that.

Doing the same in PHP is pretty much against the typical conventions you can find in most projects and the only language-level substitute we currently have
is the distinction between thrown internally [Error](https://www.php.net/manual/en/class.error.php), and generally available [Exception](https://www.php.net/manual/en/class.exception.php).
Unfortunately, from the developer's perspective, they're pretty much the same and are being handled uniformly through catching the [Throwable](https://www.php.net/manual/en/class.throwable.php) interface.
In result, they're both still invisible exceptions.
