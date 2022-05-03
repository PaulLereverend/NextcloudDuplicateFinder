#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  if [ -z "$NEXTCLOUD" ]; then
    echo "environment variable NEXTCLOUD need to bee defined"
    exit 1
  fi
  DO_NOT_SCAN=1
  load ${BATS_TEST_DIRNAME}/setup.sh
}

teardown() {
    clearTestFiles
}

@test "[$TESTSUITE] Test find-all with disabled events" {
    ./occ config:app:set --value=true duplicatefinder disable_filesystem_events   
    output=$(./occ -v duplicates:find-all -u admin)
    expectedHash="296f69b711e07c12a439e7380037e4c711fa1985d0d006b6d463c764e9580737"
    evaluateHashResult "${expectedHash}" 3 "${output}"
}

@test "[$TESTSUITE] Test upload with disabled events" {
    ./occ config:app:set --value=true duplicatefinder disable_filesystem_events   

    echo "Test" > t.txt
    curl -sS -u 'admin:admin' -T t.txt $NEXTCLOUD/remote.php/dav/files/admin/t.txt
    curl -sS -u 'admin:admin' -T t.txt $NEXTCLOUD/remote.php/dav/files/admin/t2.txt
    rm -f t.txt

    output=$(./occ -v duplicates:list -u admin)
    expectedHash="56c4396c4d371eacea44d8d183005993099d8bec360f6d43b49b4f29feda4ab2"
    evaluateHashResult "${expectedHash}" 0 "${output}"
    
    dbQuery "update oc_jobs set last_run=0,last_checked=0,reserved_at=0  where class like '%DuplicateFinder%';"
    php ./cron.php
    output=$(./occ -v duplicates:list -u admin)
    expectedHash="d243f5ebfac73b3568ea4189202b9745131f7d06756b046373f1ddc486ef92f7"
    evaluateHashResult "${expectedHash}" 4 "${output}"
}

@test "[$TESTSUITE] Test deletion with disabled events" {
    ./occ config:app:set --value=true duplicatefinder disable_filesystem_events   

    curl -sS -u 'admin:admin' -X DELETE $NEXTCLOUD/remote.php/dav/files/admin/t2.txt
    output=$(./occ -v duplicates:list -u admin)
    expectedHash="56c4396c4d371eacea44d8d183005993099d8bec360f6d43b49b4f29feda4ab2"
    evaluateHashResult "${expectedHash}" 0 "${output}"
    
    dbQuery "update oc_jobs set last_run=0,last_checked=0,reserved_at=0  where class like '%DuplicateFinder%';"
    php ./cron.php
    output=$(./occ -v duplicates:list -u admin)
    expectedHash="0a3afe9180daf73432d6dbf0c6a1bb6b215404b971a3e00f4d005f68b2272587"
    evaluateHashResult "${expectedHash}" 3 "${output}"
}
