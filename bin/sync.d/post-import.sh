#!/usr/bin/env bash
set -euo pipefail

cd "$WEB_DIR"

# SpinupTUI 1.2.2+ rewrites production's home URL (www.) and the bare domain
# itself, and lists every host it rewrote in SYNC_REMOTE_HOSTS.
if [[ -n "${SYNC_REMOTE_HOSTS:-}" ]]; then
  read -ra REMOTE_HOSTS <<< "$SYNC_REMOTE_HOSTS"
else
  # Older SpinupTUI rewrites only the bare SpinupWP domain, leaving every www
  # URL pointing at production. Remove this branch once every machine that
  # pulls this site runs SpinupTUI 1.2.2 or later.
  REMOTE_HOSTS=("$SYNC_REMOTE_HOST")
  if [[ "$SYNC_REMOTE_HOST" != www.* ]]; then
    echo "👉 [WP DB] Replacing https://www.$SYNC_REMOTE_HOST with https://$SYNC_LOCAL_HOST..."
    wp search-replace "https://www.$SYNC_REMOTE_HOST" "https://$SYNC_LOCAL_HOST" --skip-columns=guid --all-tables-with-prefix --format=count
    REMOTE_HOSTS+=("www.$SYNC_REMOTE_HOST")
  fi
fi

for host in "${REMOTE_HOSTS[@]}"; do
  echo "👉 [Elementor] Replacing https://$host with https://$SYNC_LOCAL_HOST..."
  wp elementor replace_urls "https://$host" "https://$SYNC_LOCAL_HOST"
done

echo "👉 [Elementor] Flushing CSS..."
wp elementor flush_css

# LocalDev Switcher: the import brings production's active_plugins and no
# localdev_switcher_overrides, so the site falls back to the Composer copies.
# For every web/app/plugins/localdev-{slug} checkout whose {slug} is active,
# switch to the local copy, the same way the plugin's own toggle does.
PLUGINS_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)/web/app/plugins"
if compgen -G "$PLUGINS_DIR/localdev-*/" > /dev/null; then
  echo "👉 [LocalDev Switcher] Activating and switching to localdev- copies..."
  wp plugin activate localdev-switcher
  wp eval '
    $active    = get_option( "active_plugins", array() );
    $overrides = wp_parse_args( get_option( "localdev_switcher_overrides", array() ), array( "plugins" => array(), "themes" => array() ) );
    foreach ( glob( WP_PLUGIN_DIR . "/localdev-*", GLOB_ONLYDIR ) as $dir ) {
      $local = basename( $dir );
      $base  = substr( $local, strlen( "localdev-" ) );
      foreach ( $active as $i => $plugin ) {
        if ( dirname( $plugin ) === $base && file_exists( $dir . "/" . basename( $plugin ) ) ) {
          $active[ $i ]           = $local . "/" . basename( $plugin );
          $overrides["plugins"][] = $base;
          echo "   {$base} → {$local}\n";
        }
      }
    }
    $overrides["plugins"] = array_values( array_unique( $overrides["plugins"] ) );
    update_option( "active_plugins", $active );
    update_option( "localdev_switcher_overrides", $overrides );
  '
fi

# Keep project-specific plugin toggles non-fatal.
#wp plugin deactivate spinupwp limit-login-attempts-reloaded || true
