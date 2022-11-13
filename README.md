# AA Backend Challenge

## Author

Boris Castagna - boris.castagna@gmail.com

## Description

This project is the AA BackEnd Challenge. It uses a custom docker image for PHP & Apache.

## Local Installation

Install docker-compose according to which Operating system you use by following these instructions :
https://docs.docker.com/compose/install/

Verify the docker-compose file is present at the root of the project or copy docker-compose.dev.yaml from folder infrastructure/docker/

Execute following commands in order to locally build the custom PHP image :
```
docker-compose build
```

Note: If you encounter weird behavior with the docker build command, add the flag `--no-cache` in order to rebuild all
the image layers entirely.

Start the container :
```
docker-compose up -d
```

Connect the container
```
docker exec -it aa-symfony-php bash
```

Install the dependencies
```
composer install
```

Verify that the app works by connecting to localhost:30000

Run the tests (inside the container) :
```
/vendor/bin/phpunit
```

Result
```
root@ca84e30a0f71:/var/www/html# vendor/bin/phpunit 
PHPUnit 9.5.26 by Sebastian Bergmann and contributors.

Testing 
....................                                              20 / 20 (100%)

Time: 00:02.063, Memory: 12.00 MB

OK (20 tests, 54 assertions)
```

## Notes & Comments

### Tech
This app was built using the Symfony framework in its latest version 6.2 and with PHP 8.2 (last version)

It was boostraped from scratch using the following command :
```
composer create-project symfony/skeleton:"6.2.*"
```

It uses a few other libraries from the Symfony Ecosystem such as the twig-pack (for rendering twig templated pages),
the test-pack (which provides several dependencies to produce tests in a Symfony/PHP Project) or the Mockery testing library.

### Architecture
- 1 main service CrawlManagerService that will orchestrate the main feature (crawling of pages)
- 2 services that can perform HTTP requests to get pages DOM content: 
  - one that uses cURL (for the sake of this challenge, but we could have use a library such as GuzzleHTTP client)
  - one that uses chrome-php (not optimized, in order to demo how a Single Page Application could be crawled)
- 1 service that parses the DOM content to compute (we could have use a library such as Goutte or Panther, but we parsed the DOM "manually" for this challenge)
- Several Models and DTOs classes to represent the business side of this project and easily manipulate data

### PHP8 features
Some PHP8 new features were used, such as :
- Constructor property promotion
- Union types
- readonly properties (C# style)

### PSR Notable Usages
- Usage of PSR-7 to standardize Responses of WebRequestService interfaces
- Usage of PSR-4 through Symfony logger

### Code Style
- Comments on code and functions are made in the context of this interview challenge
- Classes are implemented with the minimum useful getters & setters as well as with fluent setters (returning $this after the value assignment)
- Functions size are no more than ~40 lines (to fit on one screen entirely). It is a simple but effective way to keep code light and clear
- Early return policy : functions and methods return value the earliest possible in order to avoid stacking if and indentation which reduces the visibility
- Usage of DTOs (Data Transfer Objects) as soon as it is needed (i.e more than 1 value to return or pass through different functions or classes)
- The implementation and architecture was done with some CleanCode concepts in mind such as SOLID, KISS and DRY.

### Tests
- The library Mockery was used to handle mocks expectations testing.
- The models methods were tested first in TDD because it's easy and structuring for result quality
- Then, the services and finally the main feature service which is the CrawlManager was tested

### Possible improvements
- A nicer front-end with either some polling to refresh the crawl status or even better, react + graphQL with an appropriate subscription to refresh crawl status
- ParserService could use more than 1 discriminator attribute to know if a DOMElement is unique
- The controller could verify CrawlProcessOptionsDto() values by validating the input data sent through request
- The form input field url is readonly but can be modified in the DOM to crawl other websites
- When using the request services, the modularity can be better by using a Factory or a Facade pattern to select the appropriate Request service
- Response HTTP code more accurate handling: for example, HTTP 204 NO CONTENT could be set to stats = 0 and not null and should be not considered a failure such 404/500
- Tests scopes : more use cases, especially failures cases in CrawlManager could be tested
- Code style could have been enforced with a PHPStan high level
- And a lot of other improvements that could probably be found through code review with another or several other developers