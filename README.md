timhysniu/wp-dbdelta
======================


Quick links: [Installing](#installing) | [Usage](#usage) | [Contributing](#contributing)

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install timhysniu/wp-dbdelta`

## Usage

First we want to create a patch file which uses a default patch template. 
`wp dbdelta create insert-test-page`

Patch file will look like this:
```
<?xml version="1.0" encoding="UTF-8" ?>
<patch>
  <up>
    <sql><![CDATA[ ... ]]></sql>
  </up>
  <down>
    <sql><![CDATA[ ... ]]></sql>
  </down>
</patch>
```

By default, patches will be created in /tmp/dbdelta directory which probably isn't the place where you want to store patches.
To specify where you want your patches, with each subcommand (create, up, down), specify --patch-dir.
`wp dbdelta create create-table-1 --patch-dir=/home/tim/dbdelta`

Update SQL in &lt;sql&gt; for upgrade and downgrade. You can have as many SQL elements for either &lt;up&gt; or &lt;down&gt;.

### upgrade
Most of the time we just want the upgrade. 
You have the ability to run one or more patches, but by default all of them are run.

`wp dbdelta up` to run all patches

`wp dbdelta down 2016-05-12-insert-test-page.xml 2016-05-12-insert-test-post.xml` to run a set of patches.

### downgrade
`wp dbdelta down 2016-05-12-insert-test-page.xml`

## Contributing

Ideas are more than welcome.

Please [open an issue](https://github.com/timhysniu/wp-dbdelta/issues) with questions, feedback. Pull requests are expected.
