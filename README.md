# Inventory Sync for VentoryOne & Billbee

This repository contains the PHP script that pulls stock data from the FTP export, applies
Billbee safety buffers, and updates VentoryOne and Billbee via their official APIs.

## Run modes (dry vs. live)

The script can simulate updates (`dry-run`) or push real changes (`live`). There are two ways
to choose the mode:

1. **Default toggle** – Open `inventory_sync.php` and set the `DEFAULT_RUN_MODE` constant at the
   top of the file to either `dry` or `live`. This becomes the default when you run the script.
2. **One-off override** – When executing the script, add one of the following flags:
   * `php inventory_sync.php --dry-run` (or `--dry`) to simulate.
   * `php inventory_sync.php --live` to send updates.

You can combine these with `--verbose` or `--quiet` to control terminal logging. Use
`php inventory_sync.php --help` for a quick reminder of the available options.
