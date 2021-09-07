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
  sed "s/%%host%%/$STORAGE_HOST/g" ${BATS_TEST_DIRNAME}/assets/externalStorage.json > externalStorage.json
  for m in $(./occ files_external:list | grep -E '^\| [1-9]' | awk '{print $2}'); do
    ./occ files_external:delete -y $m
  done
  ./occ files_external:import externalStorage.json
  find ./data/admin/files/tests -type f -name '2_*' \
    -exec curl -sS -u 'test:test' -T {} http://$STORAGE_HOST/webdav/ \; \
    -exec curl -sS -u 'test:test' -T {} ftp://$STORAGE_HOST \; \
    -exec curl -sS -u 'WORKGROUP\test:test' -T {} smb://$STORAGE_HOST/public/ \; >/dev/null
}

teardown() {
    ./occ -v duplicates:clear -f
    for m in $(./occ files_external:list | grep -E '^\| [1-9]' | awk '{print $2}'); do
      ./occ files_external:delete -y $m
    done
}

@test "[$TESTSUITE] Duplicates on external storage" {
    ./occ config:app:delete duplicatefinder ignore_mounted_files
    run ./occ -v duplicates:find-all -u admin
    [ "$status" -eq 0 ]

    expectedHash="d60a2aacde91c38f29287ef2b473bc6d84d4ad7177027292d147ae3ce49cfc62"
    evaluateHashResult "${expectedHash}" 25 "${output}"
}

@test "[$TESTSUITE] Skip search for duplicates on external storage" {
    ./occ config:app:set --value=true duplicatefinder ignore_mounted_files
    run ./occ -v duplicates:find-all -u admin
    [ "$status" -eq 0 ]

    expectedHash="aec985150dc1aebb94730d39b1180f3d962d6e4d20947ed1dd3f62ce4f2ab085"
    evaluateHashResult "${expectedHash}" 25 "${output}"
}
