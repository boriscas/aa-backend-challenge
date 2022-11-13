# AA Backend Challenge

composer create-project symfony/skeleton:"5.4.*"

docker
xdebug installed but should be removed for prod

php8+ cool usages :
- constructor properties promotion
- parameter type union public function addPage(CrawledPageReport|array $page)
- readonly properties (C# style)

PSR-7 to standardize Responses of WebRequestService interfaces
PSR-4 logger of symfony implements it by default

Code Style
- Comments on code and functions are made in the context of this interview challenge
- Note on light classes with only needed setters/getters + fluents
- Golden Rule: No more than 30 lines functions. Simple but effective (except for logging for example) 
- Early return policy
- Use DTO as soon as it is needed (more than 1 value to handle). NOT arrays.
- SRP, SOLID, KISS at best...

Tests
- Use of Mockery to handle expectations and stubbs
- Tested the models methods in TDD because it's easy and structuring for result quality
- Then, tested the services and finally the feature "main" service which is the CrawlManager

Implementation
- 1 main service CrawlManagerService that will orchestrate all of that
- 3 services requests (could have used php-chrome but for the sake of the challenge, implemented a cURL raw version)
- 1 service parsers (could have use Goutte or Panther but for the sake of the challenge, parsed it manually)

Possible improvements
- nicer front-end with react with either some polling to refresh crawlstatus or even better graphQL with an appropriate 
subscription (need to limit the overkill for this challenge...)
- ParserService could use more than 1 discriminator attribute to know if a DOMElement is unique
- controller : could verify CrawlProcessOptionsDto() values
- Modularity here can even more abstract with a Factory or a Facade pattern to avoid explicit if to select the right version
- Response HTTP code more accurate handling: for example HTTP 204 NO CONTENT could be set to stats = 0 and not null and is NOT a failure like 404/500
- Tests scopes : more use case, especially failures cases in CrawlManager could be tested