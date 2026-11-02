<p align="center">
  <a href="https://wenmarkdigitial.com">
    <img src="https://wenmarkdigital.com/wp-content/uploads/2021/02/wenmark-logo_1500x430.png" alt="" width="320">
  </a>
</p>

<p align="center">WordPress boilerplate built with <a href="https://roots.io/bedrock">Bedrock</a> plus <a href="https://elementor.com">Elementor/Elementor Pro</a> and the <a href="https://elementor.com/products/hello-theme/">Hello Theme</a>.</p>

## Working with the Donation Manager plugin repository

`composer.json` installs Donation Manager from its tagged releases (`smcothrift/donation-manager`, pinned to an exact version) into `web/app/plugins/donation-manager`. Never edit that copy: `composer update` overwrites it.

Development happens in a git clone of the plugin next to it, switched on with [LocalDev Switcher](https://wordpress.org/plugins/localdev-switcher/) (installed by Composer):

```bash
git clone git@github.com:SMCoThrift/donation-manager.git web/app/plugins/localdev-donation-manager
cd web/app/plugins/localdev-donation-manager && npx bower install
```

The Bower step installs the form's front-end libraries (file upload, datepicker, input masks) into the gitignored `lib/components/vendor/`. Production gets them the same way: the SpinupWP deploy script runs `bower install` in `donation-manager` after `composer install`. Locally, run it in `donation-manager` too after a `composer update` replaces that copy.

Then use **Switch to Local** on the plugin's row in Plugins. The clone's own `AGENTS.md` covers checking which copy is active and the release flow.

A production database pull (`spinuptui pull db pickupmydonation.com --yes`, or `p` in SpinupTUI) brings production's active plugins with it. `bin/sync.d/post-import.sh` puts the setup back afterwards: it activates LocalDev Switcher and switches every plugin that has a `localdev-` checkout to it.
