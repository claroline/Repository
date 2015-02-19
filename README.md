# Repository

## installation
- clone this repository
- install composer
- composer update

## configuration
- mv src/config/parameters.json.dist parameters.json
- mv src/config/packages.ini.dist packages.ini

Then put the path you want in parameters.json (log files and output directory).
packages.ini contains a list of repository wich are clone when the --a parameter is used.
The key is the repository name, and the value is the secret token of a github new release hook if you want to automate it.

## how to use
php src/console package:generate [repository name] [--tag=x.x.x] [--a]

- repository name: the cloned repository
- --tag: a specifig tag (otherwise the latest will be cloned)
- --a: every repositories in packages.ini

## api
  [see the routing file] (https://github.com/claroline/Repository/blob/master/src/config/routes.yml)
~               
