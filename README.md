# Duplicate Finder
Install app using the Nextcloud App Store inside your Nextcloud. See https://apps.nextcloud.com/apps/duplicatefinder

You can either use the command or the gui. 

## Command usage

  ```occ duplicates:find-all [options]```
  
Depending on your NextCloud setup, the `occ` command may need to be called differently, such as `sudo php occ duplicates:find-all [options]` or `nextcloud.occ duplicates:find-all [options]`. Please refer to the [occ documentation](https://docs.nextcloud.com/server/15/admin_manual/configuration_server/occ_command.html) for more details


### Options
- `-u --user` scan files of the specified user (-u admin)
- `-p --path` limit scan to this path (--path="/alice/files/Photos")


## Preview

![alt text](https://raw.githubusercontent.com/PaulLereverend/NextcloudDuplicateFinder/master/img/preview.png)

## Special thanks

Big thanks to @chrros95