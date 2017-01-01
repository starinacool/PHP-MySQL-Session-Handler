# PHP MySQL Session Handler

A SessionHandler to save session data into a mysql database with locking.

## Usage
Create a table in your database:

    CREATE TABLE `session` (
    `id` varchar(255) NOT NULL,
    `data` mediumblob NOT NULL,
    `hits` int(10) unsigned NOT NULL DEFAULT '0',
    `archive` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `timestamp` int(10) unsigned NOT NULL,
    `gz` tinyint(3) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`,`archive`),
    KEY `time_hits` (`timestamp`,`hits`)
    ) ENGINE=TokuDB DEFAULT CHARSET=utf8
    /*!50100 PARTITION BY LIST (archive)
    (PARTITION active VALUES IN (0) ENGINE = TokuDB,
    PARTITION archive VALUES IN (1) ENGINE = TokuDB) */

Using TokuDB for better insert performance on datasets bigger then memory.
Using two partions for data activly used and stale data.

Then have a look at [example.php](example.php).<br>
Easy!


## License

Copyright (c) 2011 until today, Manuel Reinhard

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
