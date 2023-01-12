[//]: # (TITLE: Controlling my screen using MCCS)
[//]: # (DESCRIPTION: Leveraging MCCS to control the brightness of my screen)
[//]: # (DATE: 2021-08-28)

The window in my home office heads straight south. It means the amount of light I get in my room throughout the day varies from very little to a lot. In a way, it's great, because I don't feel like I'm losing the whole day sitting in a box with some artificial light. At the same time, adjusting my screen to match the light intensity in my room every two hours isn't the most convenient thing to do at all. For a long time I've been using f.lux on Windows and Redshift on Linux. These worked fine, although I've never been a fan of changing the color temperature to make the screen bearable. At some point I've dropped both of these and started adjusting the brightness and contrast levels of my screen manually. As you can imagine, it got tedious after a while. This article describes my way of solving it in a semi-automatic way.

Many people probably don't realize that monitors we're nowadays using with our computers are capable of communicating with operating systems in a more sophisticated way than just determining whether the screen is on or off. I certainly didn't. Most if not all modern monitors support *Monitor Control Command Set* (MCCS) over I<sup>2</sup>C (*eye-squared-C*) bus. The MCCS is nothing else but a standard defining a set of parameters that can be adjusted by poking the monitor over any supported protocol - in our case, I<sup>2</sup>C.

After learning all that's the case, I began looking for something that would allow me to easily leverage the MCCS. I didn't look far when I found [ddcutil](https://www.ddcutil.com/). Unfortunately, it's Linux-only. Since I'm not using Windows anymore, I'm not sure how it looks there. Last I heard, some Intel graphics drivers were exposing MCCS related settings as part of their control panel. Also, according to that tool's website, I<sup>2</sup>C is only used by external monitors. Laptop screens aren't using I<sup>2</sup>C so the following method isn't supported.

The goal now was to use *ddcutil* to change the brightness and contrast level of my monitor. Reading the manpages revealed the first thing I had to do was to obtain two unique VCP codes identifying the properties used by my monitor to control these parameters. Running `$ ddcutil vcpinfo` gave me a long list which contained these two (although YMMV):

```
VCP code 10: Brightness
   Increase/decrease the brightness of the image.
   MCCS versions: 2.0, 2.1, 3.0, 2.2
   ddcutil feature subsets: PROFILE, COLOR
   Attributes: Read Write, Continuous (normal)
```

```
VCP code 12: Contrast
   Increase/decrease the contrast of the image.
   MCCS versions: 2.0, 2.1, 3.0, 2.2
   ddcutil feature subsets: PROFILE, COLOR
   Attributes: Read Write, Continuous (normal)
```

Having the above, I was now able to call the *setvcp* command of *ddcutil* with both the VCP code for brightness and the desired brightness level. The same applied to contrast. The manpages state the value must be in range of 0 to 255, though keep in mind that the brightness and contrast levels of most monitors range from 0 to a 100.

```bash
$ ddcutil setvcp 10 <brightness-level>
```

```bash
$ ddcutil setvcp 12 <contrast-level>
```

That was it. Executing these made my monitor immediately adjust the levels to my liking.

Of course, being a programmer comes with a certain degree of a responsibility so I couldn't just stop there. I had to hook it up somehow so I didn't have to run these from the CLI. I've described my method in the [Changing the brightness and contrast using the application launcher](#changing-brightness-and-contrast-using-application-launcher).
