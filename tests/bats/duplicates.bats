#!/usr/bin/env bats

TESTSUITE="Duplicates"

setup() {
  mkdir -p data/admin/files/tests
  for i in $(seq 25)
  do
    for j in $(seq 0 $i)
    do
      echo $i > data/admin/files/tests/$i_$j.txt
    done
  done

  ./occ files:scan --all
}

@test "[$TESTSUITE] Scan for duplicates" {
  run ./occ -v duplicate:find-all -u admin -p /tests
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "file_hash" | wc -l ) -ne 25
      || "$(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')" != "6a1e9070693dd3ebdd0a696cf8bf1b5a881d260d5eb45b7269e9dd975c235fe4" ]]; then
    ret_status=$?
    echo "Output is other than expected"
    return $ret_status
  fi
}

@test "[$TESTSUITE] List duplicates" {
  run ./occ -v duplicate:list -u admin
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "file_has" | wc -l ) -ne 25
      || "$(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')" != "58d7d38903a42b7a92849784c596548a720888bd59f799c08f94523d59ae4164" ]]; then
    ret_status=$?
    echo "Output is other than expected"
    return $ret_status
  fi
}
