[//]: # (TITLE: Enabling theme auto-switching in tmux)
[//]: # (DESCRIPTION: Enabling support for Freedesktop's color scheme preference in tmux.)
[//]: # (DATE: 2024-03-04)
[//]: # (TAGS: side project, go, utility, themer, tmux, freedesktop, color scheme preference)

I live in a pretty small apartment. It's especially visible in the room me and my fiancee are using as our office. Our desks are placed in
such a way that makes it possible to reach them and walk around the room without bumping into each other but that's pretty much the only
good thing about it. The room is constantly either too bright or too dark, which requires me to fiddle with my display's brightness so it's
not burning out my retinas, or getting me a headache from squeezing my eyes for an extended period of time. Nowadays I'm doing it using [my
small ddcutil-based utility](/articles/changing-your-displays-brightness-using-the-system-launcher) I wrote in bash a while ago.

I'm also a heavy user of the [Freedesktop's color scheme
preference](https://github.com/flatpak/xdg-desktop-portal/blob/d7a304a00697d7d608821253cd013f3b97ac0fb6/data/org.freedesktop.impl.portal.Settings.xml#L33-L45)
supported natively in Gnome. Whenever I feel the display is set alright, but Gnome's theme is still making me uncomfortable, I'm toggling
the appearance to either dark or light, depending on my needs. It mostly does the job. The whole DE switches its color scheme, including
Firefox & Chrome and any website that supports the
[prefers-color-scheme](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-color-scheme) media feature. Sadly, not all
applications are supported - Slack on Linux is by far the biggest offender here, because there's no way to change its theme from the
outside. But, the list also includes applications like Alacritty, (neo)vim, emacs, or... tmux. None of them support the Freedesktop's color
scheme preference natively.

After a while, the process of manually switching themes got really tedious, which lead me to implementing
[Themer](https://github.com/d1823/themer). It's a daemon, running as a Systemd user service, listening for the changes of the theme
preference, and executing the configured adapters to trigger color scheme changes in applications that don't support it natively. At the
time of writing this, it supports switching symlinks, talking to tmux, Alacritty and Konsole.

### Color scheme preference & tmux

The first step is to put the light and dark themes into separate files. I've chosen to follow the XDG spec, and placed them in
`~/.config/tmux/themes` directory, as `dark-theme.conf` and `light-theme.conf`.

Next, I've made sure to symlink one of them to the `~/.config/tmux/themes/current-theme.conf`, and let tmux know it needs to source it by
adding `source-file ~/.config/tmux/themes/current-theme.conf` to my `~/.config/tmux/tmux.conf`. At that point, reloading my tmux config loaded the chosen
theme.

To make tmux switch its theme automatically once the color scheme preference changes, you need to set up
[Themer](https://github.com/d1823/themer). Reference its README file to find out how to do that. If you're already past that, we need to
append the relevant configuration to the `~/.config/themer/config.json`. In my case, the configuration looks as follows:

```json
{
    "no_preference_fallback": "dark",
    "adapters": [
        {
            "adapter": "tmux",
            "dark_preference_file": "/home/dawid/.config/tmux/themes/dark-theme.conf",
            "light_preference_file": "/home/dawid/.config/tmux/themes/light-theme.conf",
            "target_file": "/home/dawid/.config/tmux/themes/current-theme.conf",
            "tmux_config_file": "/home/dawid/.config/tmux/tmux.conf"
        }
    ]
}
```

Once that's done, restart the deaemon and switch your color scheme - it should trigger tmux to switch as well.

### How?

As I stated before, the daemon listens on the DBus for all changes done to the color scheme preference. Whenever the preference changes, it
executes the configured adapters with the new value. In case of tmux, it replaces the `current-theme.conf` symlink with the correct theme
and executes `tmux source-file ~/.config/tmux/tmux.conf` which triggers it to reload the configuration in all its running sessions. Check
the [source code of all the available adapters](https://github.com/d1823/themer/blob/master/internal/adapter/adapter.go) if you're
interested in the details.
