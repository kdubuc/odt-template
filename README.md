# ODT

kdubuc/odt is a library to quickly generate Open Document Text-files using a simple templating mechanism. Spiritual successor to [odtphp](https://github.com/cybermonde/odtphp).

UNDER ACTIVE DEVELOPMENT - EXPECT BC BREAKS

## Install

Via Composer

``` bash
$ composer require kdubuc/odt
```

## Documentation

### Tags

Use Date, Field, QrCode and other tags to build your templates.

##### Conditional

The Conditional Tag allows to render contents only if condition is true.

```
[IF key]
[/IF key]
```

##### Date

The Date Tag allows to display date values (fr locale only).

```
{date:key}
```

##### Field

The Field Tag allows to display different string values.

```
{field:key}
```

##### Image

The Image Tag allows adding images from URL.

```
{image:key}
```

##### Segment

The Segment Tag allows to group other tags and iterate array data. The Segment is used as a template to render each row in the data set.

Usage :
```
[SEGMENT key]
[/SEGMENT key]
```

##### QrCode

The QrCode Tag components allow rendering QR codes from any field in the data set.

Usage :
```
{qrcode:key,size:150}
```

Options :
- size : QrCode size in pixel
- margin : Margin in pixel applied to QrCode

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email kevindubuc62@gmail.com instead of using the issue tracker.

## Credits

- [Kévin DUBUC](https://github.com/kdubuc)
- [All Contributors](https://github.com/kdubuc/odt/graphs/contributors)

## License

The CeCILL-B License. Please see [License File](LICENSE.md) for more information.
