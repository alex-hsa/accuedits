# OpenAI / ChatGPT / AI Search Integration

The Drupal OpenAI module makes it possible to interact with the
[OpenAI API](https://openai.com/) to implement features using
various API services.

The OpenAI module aims to provide a suite of modules and an API foundation
for OpenAI integration in Drupal for generating text content, images, content
analysis and more. OpenAI is the company behind artificial generational
intelligence products that powers applications like ChatGPT, GPT-3, GitHub
CoPilot, and more. Our goal is to find ways of augmenting and adding assistive
AI tech leveraging OpenAI API services in Drupal.

For a full description of the module, visit the
[project page](http://drupal.org/project/openai).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](http://drupal.org/project/issues/openai).

## Table of contents

- Requirements
- Installation
- Included modules
- Webserver Streaming Support
- Planned Functionality
- Maintainers

## Requirements

This module is tested on Drupal 10.x.

You are required to provide an OpenAI key before you can use
any of the provided services.

## Installation

Enable the core OpenAI module and one or more submodules that meet your need.

### Included modules

- OpenAI Audio
- OpenAI ChatGPT
- OpenAI CKEditor integration
- OpenAI Content Tools
- OpenAI DALL·E
- OpenAI Log Analyzer
- OpenAI ChatGPT Devel Generate
- OpenAI Embeddings
- OpenAI Prompt
- OpenAI TTS

## Webserver Streaming Support

The following are basic configurations for enabling streamed response
support when using Apache in DDEV or Docker4Drupal setups.

If you are using DDEV with nginx, no additional change is needed.

If you are using DDEV with apache, in the folder `.ddev/apache`
create the file `apache-streaming.conf` with the following:

```apacheconf
<IfModule proxy_fcgi_module>
    <Proxy "fcgi://localhost/" enablereuse=on flushpackets=on max=10>
    </Proxy>
</IfModule>
```

If you are using Docker4Drupal:

```apacheconf
<Proxy "fcgi://php:9000/">
  ProxySet enablereuse=on flushpackets=on max=10
</Proxy>
```

If your `APACHE_BACKEND_HOST` is called something else than 'php', replace the
value above with that name. If your `APACHE_BACKEND_PORT` is not 9000, also
change the above.

If your web server is Nginx based, you will need to implement the equivalent
to enable the functionality above.

If your web server **does not** support this capability, the response will
behave normally as it did before.

Please note that modifying web server settings could create a security risk -
double-check your settings and check with your internal IT team before deploying
to production. You need to ensure the security of both your Apache server and
the FastCGI server as they work together to serve your application, this module
makes no guarantees in regard to your webserver configuration.

## Planned functionality
- Field/field widget integration
- Content moderation
- Views integration
- and more

## Maintainers

- Kevin Quillen - [kevinquillen](https://www.drupal.org/u/kevinquillen)
- Laurence Mercer - [laurencemercer](https://www.drupal.org/u/laurencemercer)
- Raffaele Chiocca - [rafuel92](https://www.drupal.org/u/rafuel92)
- Julien Alombert - [Julien Alombert](https://www.drupal.org/u/julien-alombert)
- Scott Euser - [scott_euser](https://www.drupal.org/u/scott_euser)
