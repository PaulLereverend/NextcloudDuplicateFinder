# Duplicate Finder
Install app using the Nextcloud App Store inside your Nextcloud. See https://apps.nextcloud.com/apps/duplicatefinder

Click on the icon and find if you have duplicate files.

You can either use the command or the cron job to do a full scan.
Each time a new file is uploaded or a file is changed the app automatically checks if a duplicate of this file exists.

## Usage
Beginning with version 0.0.7 the detection of duplicates has changed. If you're updating from an older version you don't have to do anything special.
There are there are three possible ways duplicates can be detected.
1. Event-based-detection
   For every new or changed file Nextcloud creates an internal event. The app is listening to these and analyse if the file has a duplicate
2. Background-job-based-detection
   With an interval of 5 days a background job is executed, if you have enabled either cron, webcron or ajax based background jobs. This is only for events that have not been processed for any reason or a file was added manually.
3. Command-based-detection
   A scan for duplicates can be forced by using the occ-command (please see #Command usage)

Normally the detection-methods should by used in the order as listed, but if you are installing the app on an existing installation it can be quite useful to start with a full scan by using the command-based-detection.

## Command usage

  `occ [-v] duplicates:ACTION [options]`

Depending on your NextCloud setup, the `occ` command may need to be called differently, such as `sudo php occ duplicates:find-all [options]` or `nextcloud.occ duplicates:find-all [options]`. Please refer to the [occ documentation](https://docs.nextcloud.com/server/15/admin_manual/configuration_server/occ_command.html) for more details

If you increase the verbosity of the occ command, the shows a little bit more what is currently done (e.g. what file is currently scanned).

    ACTION
    find-all    The command scans the files for duplicates. By using the options the scan can be limited
      options
        -u, --user scan files of the specified user (-u admin)
        -p, --path limit scan to this path (--path="./Photos"). The path is relative the root of each user or the specified user.
    list        The command lists all duplicates that have been found until yet. If no option is given duplicates across users are shown.
      options
        -u, --user list only duplicates of the specified user (-u admin)
    clear       The command will clear all information that has been stored in the database
      options
        -f, --force the flag forces to do the cleanup. _attention_ you will not be asked any questions

## Preview

![Preview of the GUI](https://raw.githubusercontent.com/PaulLereverend/NextcloudDuplicateFinder/master/img/preview.png)
