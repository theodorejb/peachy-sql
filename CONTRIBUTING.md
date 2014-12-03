# Contributing

Contributions to PeachySQL are welcome and encouraged.
If you'd like to help out, you've come to the right place!

## Dev environment setup

1. Clone the repository: `git clone git@github.com:theodorejb/peachy-sql.git`
2. Install dependencies: `composer install`

## Tests

From a console in working directory, execute `"vendor/bin/phpunit"` to run all unit tests.

### Database setup

By default, database tests for MySQL will attempt to run on on database named
`PeachySQL` as the root user with a blank password. To run tests on SQL Server
or override connection settings, create a `config.user.php` file in the test
directory (see `test/config.php`). Database tests can be skipped entirely by
running `"vendor/bin/phpunit" --testsuite no-db`.

## Coding guidelines

Submitted code should comply with the
[PSR-1 basic coding standard](http://www.php-fig.org/psr/psr-1/) and
[PSR-2 coding style guide](http://www.php-fig.org/psr/psr-2/). In a nutshell,
this means 4-space indentation (not tabs), UTF-8 encoding, and specific rules
for whitespace, capitalization, and namespacing (follow the links for details).
