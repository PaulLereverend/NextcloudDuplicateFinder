# Duplicate Finder
Install app using the Nextcloud App Store inside your Nextcloud. See https://apps.nextcloud.com/apps/duplicatefinder

Click on the icon and find if you have duplicate files.

You can either use the command or the cron job to do a full scan.
Each time a new file is uploaded or a file is changed the app automatically checks if a duplicate of this file exists.

## Command usage

  ```occ duplicates:find-all [options]```

Depending on your NextCloud setup, the `occ` command may need to be called differently, such as `sudo php occ duplicates:find-all [options]` or `nextcloud.occ duplicates:find-all [options]`. Please refer to the [occ documentation](https://docs.nextcloud.com/server/15/admin_manual/configuration_server/occ_command.html) for more details

If you increase the verbosity of the occ command, the file that is currently scanned and the hash will be displayed

### Options
- `-u --user` scan files of the specified user (-u admin)
- `-p --path` limit scan to this path (--path="/alice/files/Photos")

## Preview

![Preview of the GUI](https://raw.githubusercontent.com/PaulLereverend/NextcloudDuplicateFinder/master/img/preview.png)
