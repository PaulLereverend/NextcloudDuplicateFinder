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
    clearTestFiles
}

@test "[$TESTSUITE] Duplicates on external storage" {
    ./occ config:app:delete duplicatefinder ignore_mounted_files
    
    output=$(./occ -v duplicates:find-all -u admin)
    expectedHash="2ba3a0e386a9784998f74b45a542871db0d84dccb708c69c908d408b74fafbab"
    evaluateHashResult "${expectedHash}" 3 "${output}"
}

@test "[$TESTSUITE] Skip search for duplicates on external storage" {
    ./occ config:app:set --value=true duplicatefinder ignore_mounted_files
    
    output=$(./occ -v duplicates:find-all -u admin)
    expectedHash="cbdc41d9d24253c54da8cd3087ecde71b45c7c9f328336d1247d2032b3a5aa28"
    evaluateHashResult "${expectedHash}" 3 "${output}"
}
