# Islandora Attach Media

## Introduction

Islandora 8/9 module that provides a Drush command to attach a file to a node. Intended for use with files that are too large to upload using Drupal's standard web interface.

This utility creates the File and Media entities in Drupal, but it does not touch the files themselves. In other words, if the filesystem schema is 'public://' or 'private://', the file must exist there already; if the schema is 'fedora://', the binary resource must exist in Fedora already. This is a feature, not a deficiency, since Drupal's FileSystem.php's saveData() uses PHP's `file_get_contents()` and `file_put_contents()`, which are limited to handling files that are smaller than the PHP's configured memory limits. This utility is intended to avoid using those PHP functions completely so files larger than PHP's limits can be used as Drupal media.

## Do not use

This is a pre-alpha, proof of concept module. It might not even make it past this point. DON'T INSTALL IT YET.

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
