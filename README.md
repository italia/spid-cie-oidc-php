# SPID/CIE OIDC Federation Relying Party, for PHP

[![spid-cie-oidc-php CI](https://github.com/italia/spid-cie-oidc-php/workflows/Setup%20Check%20CI/badge.svg)](https://github.com/italia/spid-cie-oidc-php/workflows/Setup%20Check%20CI/badge.svg)
![Apache license](https://img.shields.io/badge/license-Apache%202-blue.svg)
[![Get invited](https://slack.developers.italia.it/badge.svg)](https://slack.developers.italia.it/)
[![Join the #spid openid](https://img.shields.io/badge/Slack%20channel-%23spid%20openid-blue.svg)](https://developersitalia.slack.com/archives/C7E85ED1N/)

> ⚠️ This project is a WiP

<img src="doc/spid-cie-oidc-php.gif" width="500" />

The SPID/CIE OIDC Federation Relying Party, for PHP

## Summary

* [Features](#features)
* [Setup](#setup)
* [Docker](#docker)
* [Contribute](#contribute)
    * [Contribute as end user](#contribute-as-end-user)
    * [Contribute as developer](#contribute-as-developer)
* [Useful links](#useful-links)
* [License and Authors](#license-and-authors)

## Features

- Interactive setup
- Wizard for certificates generation
- Bootstrap template
- Hooks plugins
- Simple API
- Proxy functions
- Ready to use

## Setup

```
git clone https://github.com/italia/spid-cie-oidc-php.git
composer install
```
After setup go to /<i>service_name</i>/oidc/rp/authz
where <i>service_name</i> is the service name configured during setup

## Docker

Start the basic example project is as simple as run:
```
docker pull linfaservice/spid-cie-oidc-php
docker run -it -p 8002:80 -v $(pwd)/config:/var/www/spid-cie-oidc-php/config linfaservice/spid-cie-oidc-php
```
On the first run the setup will ask for configurations.
All configurations will be saved in the ./config directory.


## Contribute

Your contribution is welcome, no question is useless and no answer is obvious, we need you.

#### Contribute as end user

Please open an issue if you've discoveerd a bug or if you want to ask some features.

#### Contribute as developer

Please open your Pull Requests on the __dev__ branch. 
Please consider the following branches:

 - __main__: where we merge the code before tag a new stable release.
 - __dev__: where we push our code during development.
 - __other-custom-name__: where a new feature/contribution/bugfix will be handled, revisioned and then merged to dev branch.

In this project we adopt [Semver](https://semver.org/lang/it/) and
[Conventional commits](https://www.conventionalcommits.org/en/v1.0.0/) specifications.


## Useful links

* [Openid Connect Federation](https://openid.net/specs/openid-connect-federation-1_0.html)
* [SPID/CIE OIDC Federation SDK](https://github.com/italia/spid-cie-oidc-django)


## License and Authors

This software is released under the Apache 2 License by:

- Michele D'Amico (@damikael) <michele.damico@linfaservice.it>.
