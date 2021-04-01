## NOTE
The project was built to help the friend of mine about 8 months ago and it's prohibited to copy any part of the code and use it for commercial purposes.

## Description
The idea of the business is to build the totally automatic process of dropshipping of fashion products from EU countries to non-eu countries.

It was built in a rush within few weeks to just make a proof of concept, that's why the tests are still in progress.

An application has few services and 2 flows.

The first flow is crawling/parsing flow to get all the data of the needed products by category and other filters on the vendors websites.

### Crawling flow

Please see the whiteboard DRAW:
https://ilkinalibayli.com/images2baku/crawling.jpeg

1) Our administrator adds the category title and the link in the control panel. Then the new row in a queue (database) is created with the status "new".
2) We can have one or more (any amount) of workers (parsers) at the same time. They just go to the database and get any task with FIFO principle. And start to crawl the product from the vendor's website. Then saves it into the products table in the database.
3) In parallel with the parsing, the workers are also doing the API synchronization with our frontend Shopify.
4) Every night, we set all the products as not synchronized.
5) In parallel, we have a cronjob to synchronize product stocks between vendor and our Shopify frontend.


### Order flow

Please see the whiteboard DRAW:
https://ilkinalibayli.com/images2baku/order.jpeg

1) Client makes an order using Shopify frontend
2) Shopify sends a webhook to our "Order Proxy microservice"
3) Order Proxy makes a request to the invoice generator microservice to get it in PDF format
4) Then Order Proxy sends the request to the shipping company to make it ready for the package from the vendor with the invoice and all the needed user data for shipping.
5) Then Order Proxy makes an automatic order (using Selenium) on the vendor's website and specifies the shipping company's warehouse address, and also the customer id within abroad shipping company to map it to the address in the abroad country.
6) Vendor sends the product to the shipping company within 1-2 days.
7) Shipping company sends the product to the client abroad within 1 week.


The main core of the project is inside the /public/parser/Selenium/

## Requirements:
1) Docker
2) PHP 8.1 (latest for April 2021) support in your IDE

## How to:

### Start the project
cd public
make start

### To see the database, phpmyadmin
http://localhost:8080/index.php
server: (leave empty)
user: `db_user`
pass: `db_user_pass`
database: `app_db`

### Project config
`public/parser/Selenium/App/ProjectConfig.php`

### Crontab
The content of the crontab is inside `public/sudocrontab` file. Every time the parser service loads up, it copies it to `sudo crontab` automatically.

## NOTE
The parsers are turned off by default to not do it on the local environment due to security reasons (your IP might be blocked etc.).
