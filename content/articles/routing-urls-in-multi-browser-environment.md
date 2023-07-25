[//]: # (TITLE: Browser Matcher: HTTP(s) scheme handler)
[//]: # (DESCRIPTION: A middleman between the link you've clicked and your default browser(s).)
[//]: # (DATE: 2023-07-25)
[//]: # (TAGS: side project, go, utility, browser-matcher)

I'm using multiple browsers: Firefox for regular web browsing, and Chrome for
work. Additionally, I’m utilizing multiple Chrome profiles, one for every
client I work with, to have a dedicated browser space with separate extensions
and sessions. This setup works… as long as I’m within a web browser. When I’m
in a terminal, or in Slack, or any other non-browser context, all these
assumptions about particular browser or profiles being used for specific sites
no longer apply. The operating system doesn’t know about them, and the only way
to control what browser is going to handle the URL I just clicked, is to set
the default browser in the system settings. That’s not enough.

Initially, I went ahead and made sure each application that allowed it was set
with its dedicated web browser. Unfortunately, after a while I figured it’s not
enough. There were applications that either didn’t support overriding the
default web browser, or I’ve already used them in multiple contexts which made
overriding a default web browser useless. I knew the process of determining the
correct browser to handle the URL has to apply some basic logic and it’s not
doable with the system or application settings. That’s how I got the idea of a
middleman.

When you click a URL, every modern OS will parse it and look up the configured
handler based on the schema. For HTTP & HTTPS URLs, it’s going to look for the
default web browser. I decided to use this setup to my advantage, and replace
the default web browser with a small utility that’s only responsibility is to
launch the correct browser with the URL it got as an input. The logic behind it
was based on the URL being matched against a list of regexp patterns - the
first match determined the correct browser. I’ve put the configuration in a
JSON file, tweaked it a few times over the next weeks and forgot about it
completely afterwards. It’s been working flawlessly ever since.

If you’re interested, check out the [browser-matcher repository on
GitHub](https://github.com/d1823/browser-matcher). It’s just a small Go
application that you might find useful if you’re using multiple browser, or
browser profiles like I do.
