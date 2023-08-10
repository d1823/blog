[//]: # (TITLE: Web Components as native form elements)
[//]: # (DESCRIPTION: Creating custom Web Components as native form elements.)
[//]: # (DATE: 2023-08-10)
[//]: # (TAGS: js, web components, short thoughts)

When I’m working on my personal projects, I like to stay as low level as possible. In most cases, I’d be happy to even skip JavaScript altogether and just rely on the limited browser features. Obviously, with today’s expectations related to applications interactivity, it’s not always possible. Even personal projects should still feel modern enough.

Recently, I tasked myself with implementing a frontend for one of the applications of mine with pure web components. No dependencies, aside from necessary polyfills. At some point I started working on one form that required a few custom controls to make it easier to use. I was doing some very basic form handling on the web-component level and decided to explore the idea of using custom controls as if they were native - that is, no custom data handling and the only thing you ever have to do to grab the form state is `new FormData(e.target)` in the submit handler. Years ago, when I last tried something like that, it was impossible and the developer had no other option than to serialize the control’s value to a native control to make sure the form can pick it up. How does it look right now?

Meet [form associated web components](https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/attachInternals#examples) with [ElementInternals](https://developer.mozilla.org/en-US/docs/Web/API/ElementInternals). This nifty conjunction allows us to tie any web component into the parent form. As a result, we can create custom controls that don’t require any custom data handling. Whatever you do internally is your thing. As long as you call [setFormValue](https://developer.mozilla.org/en-US/docs/Web/API/ElementInternals/setFormValue), you can be sure it’s going to be picked up during form submission. Obviously, you need to make sure the set value is serialized into something that can be serialized into a form value. [The browser support is there as well](https://caniuse.com/?search=ElementInternals).

Check out the additional examples on MDN. It’s an insanely good feature.
