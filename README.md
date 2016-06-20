# homepage-php
The code used for the pages written in PHP on https://www.uni-muenster.de/Physik.FSPHYS/.

## Note on localization
Localization is defined in `localization.inc`, which detects the correct locale
to use from its location on the file system. In order for this to work, a copy
of `localization.inc` must be present in each of the subtrees for the different
languages (`…/en/…`, `…/fr/…` etc.). (Note that the `.inc` files must be
present in all the subtrees anyway because they are included through a path
relative to the executing script’s directory). Alternatively, a script can define
the constant `LOCALE` (e.g. `const LOCALE = 'de_DE'`) *before* including
`localization.inc` and set the default locale used by `localization.inc` in
this way.

