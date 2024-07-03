This Drupal module contains the core framework and related code for my
[component system](component_explainer.md).

**Warning**: while this is generally production-ready, it's not guaranteed to
maintain a stable API and may occasionally contain bugs, being a
work-in-progress. Stable releases may be provided at a later date.

----

# Requirements

* [Drupal 10](https://www.drupal.org/download)

* [Composer](https://getcomposer.org/)

## Front-end dependencies

To build front-end assets for this project, [Node.js](https://nodejs.org/) and
[Yarn](https://yarnpkg.com/) are required.

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
"drupal/ambientimpact_core": {
  "type": "vcs",
  "url": "https://github.com/Ambient-Impact/drupal-ambientimpact-core.git"
}
```

### Installing

Once you've completed all of the above, run `composer require
"drupal/ambientimpact_core:^2.0@dev"` in the root of your project to have
Composer install this and its required dependencies for you.

## Front-end assets

To build front-end assets for this project, you'll need to install
[Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/).

This package makes use of [Yarn
Workspaces](https://yarnpkg.com/features/workspaces) and references other local
workspace dependencies. In the `package.json` in the root of your Drupal
project, you'll need to add the following:

```json
"workspaces": [
  "<web directory>/modules/custom/*"
],
```

where `<web directory>` is your public Drupal directory name, `web` by default.
Once those are defined, add the following to the `"dependencies"` section of
your top-level `package.json`:

```json
"drupal-ambientimpact-core": "workspace:^2"
```

Then run `yarn install` and let Yarn do the rest.

### Optional: install yarn.BUILD

While not required, [yarn.BUILD](https://yarn.build/) is recommended to make
building all of the front-end assets even easier.

----

# Building front-end assets

This uses [Webpack](https://webpack.js.org/) and [Symfony Webpack
Encore](https://symfony.com/doc/current/frontend.html) to automate most of the
build process. These will have been installed for you if you followed the Yarn
installation instructions above.

If you have [yarn.BUILD](https://yarn.build/) installed, you can run:

```
yarn build
```

from the root of your Drupal site. If you want to build just this package, run:

```
yarn workspace drupal-ambientimpact-core run build
```

----

# Major breaking changes

The following major version bumps indicate breaking changes:

* 1.x:

  * Has been [`git subtree split`](https://shantanoo-desai.github.io/posts/technology/git_subtree/) from [`Ambient-Impact/drupal-modules`](https://github.com/Ambient-Impact/drupal-modules/tree/8.x) into a standalone package; version has been reset to 1.x.

  * Requires Drupal 9.5.

  * Increases minimum version of [Hook Event Dispatcher](https://www.drupal.org/project/hook_event_dispatcher) to 3.1, removes deprecated code, and adds support for 4.0 which supports Drupal 10.

* 2.x:

  * Requires [Drupal 10](https://www.drupal.org/project/drupal/releases/10.0.0) due to non-backwards compatible change to [`\Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher::dispatch()`](https://git.drupalcode.org/project/drupal/-/commit/7b324dd8f18919fc4d728bdb0afbcf27c8c02cb2#6e9d627c11801448b7a793c204471d8f951ae2fb).

  * Requires [Hook Event Dispatcher](https://www.drupal.org/project/hook_event_dispatcher) 4.0 which supports Drupal 10.
