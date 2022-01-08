[//]: # (TITLE: Launching displaymgr without sudo)
[//]: # (DESCRIPTION: How to configure sudoers to use displaymgr without sudo)
[//]: # (DATE: 2022-01-08)

Last time I've described a relatively easy way to control the brightness and contrast of your screen. It didn't occur to me that what I've shown is kind of incomplete. The resulting utility does work but it requires you to use sudo to run it. It also means that launching it via `.desktop` file won't work unless the WM is running with elevated privileges (please don't do that, seriously).

Given that you've put the `displaymgr` binary at `/usr/local/bin/displaymgr`, you can execute `visudo /etc/sudoers.d/displaymgr` and save the file with the content below. This configuration will let the displaymgr binary run with elevated privileges by default, for every user. In other words, running it is as simple as `displaymgr default`, so no sudo, or sudo password is required.

```
Cmnd_Alias DISPLAYMGR=/usr/local/bin/displaymgr
ALL ALL=NOPASSWD: DISPLAYMGR
```
