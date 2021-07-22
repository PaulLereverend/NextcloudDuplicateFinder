#!/usr/bin/env bats
TESTSUITE="duplicatefinder"
load helper.sh

setup() {
  if [ -z "$STORAGE_HOST" ]; then
    echo "environment variable STORAGE_HOST need to bee defined"
    exit 1
  fi
  DO_NOT_SCAN=1
  load ${BATS_TEST_DIRNAME}/setup.sh
  ./occ app:enable files_external
  sed "s/%%host%%/$STORAGE_HOST/g" ${BATS_TEST_DIRNAME}/assets/externalStorage.json > $BATS_TEST_TMPDIR/externalStorage.json
  for m in $(./occ files_external:list | grep -E '^\| [1-9]' | awk '{print $2}'); do
    ./occ files_external:delete -y $m
  done
  ./occ files_external:import $BATS_TEST_TMPDIR/externalStorage.json
  find ./data/admin/files/tests -type f -name '2_*' \
    -exec curl -u 'test:test' -T {} http://$STORAGE_HOST/webdav/ \; \
    -exec curl -u 'test:test' -T {} ftp://$STORAGE_HOST \; \
    -exec curl -u 'WORKGROUP\test:test' -T {} smb://$STORAGE_HOST/public/ \; >/dev/null 2>&1
}

@test "[$TESTSUITE] Duplicates on external storage" {
    run ./occ -v duplicates:find-all -u admin
    [ "$status" -eq 0 ]

    expectedHash="d60a2aacde91c38f29287ef2b473bc6d84d4ad7177027292d147ae3ce49cfc62"
    evaluateHashResult "${expectedHash}" 25 "${output}"
}
