<p align="center">
  <a href="https://wenmarkdigitial.com">
    <img src="https://wenmarkdigital.com/wp-content/uploads/2021/02/wenmark-logo_1500x430.png" alt="" width="320">
  </a>
</p>

<p align="center">WordPress boilerplate built with <a href="https://roots.io/bedrock">Bedrock</a> plus <a href="https://elementor.com">Elementor/Elementor Pro</a> and the <a href="https://elementor.com/products/hello-theme/">Hello Theme</a>.</p>

## Working with the Donation Manager plugin repository

When deploying this project, be sure `composer.json` is referencing the Donation Manager plugin repository as defined in composer.production.json. When doing local development, copy `composer.local.json` into `composer.json`.

After copying `composer.local.json` into `composer.json`, do the following to update the project to use the symlinked repository for the Donation Manager plugin:

1. Clear Composer's cache: `composer clear-cache`
2. Update the dependency for the plugin: `composer update smcothrift/donation-manager`
3. Verify the symlink.

Once you're ready to deploy, do the same thing as above but copy `composer.production.json` to `composer.json` before doing steps 1-3.
