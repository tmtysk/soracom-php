# soracom-php

unofficial soracom api client for PHP.

## Install

```
$ composer require cu/soracom:dev-master
```

## Usage

```
$c = new CU\Soracom\Client();

// list subscribers.
$c->subscribers();

// register subscriber.
$c->registerSubscriber(['imsi' => 'XXXXXXXXXXXXXXXXXX', 'registrationSecret' => 'XXXXX']);
```
