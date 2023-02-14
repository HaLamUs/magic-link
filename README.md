# Simple web application using Magic Link

This application includes 2 part:
- FE: Using Next.js
- BE: Written in PHP 

### Prerequisites

- [PHP 5.6 and above](https://www.php.net/downloads.php)
- [MySQL](https://www.mysql.com/downloads/)
- [Composer](http://getcomposer.org/)

## Getting Started

Clone this project with the following commands:

```bash
git clone https://github.com/HaLamUs/magic-link.git
```

### Configure the application

```
CREATE TABLE `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tx_id` varchar(255) NOT NULL,
  `txhash` varchar(255) NOT NULL,
  `methodId` varchar(255) NOT NULL,
  `timeStamp` varchar(255) NOT NULL,
  `fromW` varchar(255) NOT NULL,
  `toW` varchar(255) NOT NULL,
  `account_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
);

ALTER TABLE transaction ADD UNIQUE INDEX(tx_id);

```

Copy `.env.example` to `.env` file and enter your credential details.

```bash
cp .env.example .env
```
## Development

### Server (PHP APIs)

Install the project dependencies and start the PHP server:


```bash
composer install
```

```bash
php -S localhost:8000 -t api
```

## Todos
- [ ] Refactor constants
- [ ] Monitor system
- [ ] Logger system

## Reference
https://github.com/shahbaz17/php-rest-api

## Author

This repo was developed by [@lamha](https://github.com/HaLamUs). 
Follow or connect with me on [my LinkedIn](https://www.linkedin.com/in/lamhacs). 


## License

MIT License
