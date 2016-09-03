# Rila Framework for WordPress
Rila is a framework, focused on front-end WordPress development. It includes a set of lot of lightweight, extendable class wrappers, which let you structure you code properly in a MVC manner.

## Features
- Wrapper classes, which allow easy and seamless access to WordPress data within templates.
- Supports pure PHP, Twig and Blade templates out of the box. Other template engines can be easily used.
- Focuses on performance: Data is available on demand and is never loaded in advance.
- Integrates seamlessly with plugins for custom fields, has special helpers for Advanced Custom Fields.
- Built with extensibility in mind: You can finally separate your logic from your views properly.
- While being ideal as a framework, the plugin can be used as a library too.
- Well-documented code and thorough documentation

## Quick example
Your *single.php* file:

```php
rila_view( 'single' )->render();
```

Your *single.twig* file:
```twig
{% extends "base.twig" %}

{% block content %}
	<h1>{{ post.title }}</h1>
	<p class="entry-meta">Posted on {{ post.date }} at {{ post.date.time }} by {{ post.author.name }}</p>

	<div class="rte">
		{{ post.content }}
	</div>

	{% if post.categories %}
		<p>Belongs to {{ post.categories }}</p>
	{% endif %}
{% endblock %}
```

Beautiful, isn't it?

## Installation
1. Download this repository as a WordPress plugin
2. Make sure that you have Composer installed and run `composer install` within the plugin's folder
3. Activate the plugin
4. Read the [wiki](https://github.com/RadoslavGeorgiev/rila-framework/wiki) and benefit

## Coolest feature: Data mapping
The concept of data mapping allows you to have consistent and predictable properties for item attributes, meta and more. As an example from within the framework, you could have a definition like this:

```php
$this->translate(array(
  'image' => '_thumbnail_id'
));

$this->map(array(
  '_thumbnail_id' => 'image'
));
```

That code would allow you to use `post.image` instead of `post._thumbnail_id` and the value would be an actual image object, so you can go ahead and simply use this within your templates:

```twig
{{ post.image }}
```

Mapping does not only apply to simple objects though - it's being heavily used throughout the whole plugin. You can read more about the topic in the wiki.

## Functionality and docs
All of the functionality of the plugin is described in the [Wiki](https://github.com/RadoslavGeorgiev/rila-framework/wiki).

## Author
The framwork is being developed by me, Radoslav Georgiev, web developer at [DigitalWerk](https://www.digitalwerk.agency).

Contributions and pull requests are welcome ;)
