#!/usr/bin/env bats

TESTSUITE="Duplicates"

setup() {
  if [[ -z $(./occ user:list | grep "tuser:") ]]; then
    export OC_PASS="test3588347"
    ./occ user:add --password-from-env  --display-name="Test User" tuser
  fi

  mkdir -p data/{admin,tuser}/files/tests
  #Include 0 to have one file per user (edge case where duplicate exist only because of one file per user)
  for i in $(seq 0 25)
  do
    for j in $(seq 0 $i)
    do
      echo $i > data/admin/files/tests/${i}_${j}.txt
      echo $i > data/tuser/files/tests/${i}_${j}.txt
    done
  done

  ./occ files:scan --all
}

@test "[$TESTSUITE] Scan for duplicates" {
  run ./occ -v duplicate:find-all -u admin -p /tests
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "file_hash" | wc -l ) -ne 25
      || "$(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')" != "2aaa6522e780936689141b004dddfbcd4306eb843072e8976fad24fdf1d03ca8" ]]; then
    ret_status=$?
    echo "Output is other than expected"
    return $ret_status
  fi
}

@test "[$TESTSUITE] List duplicates" {
  run ./occ -v duplicate:list -u admin
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "file_hash" | wc -l ) -ne 25
      || "$(echo "$output" | grep "/admin/files/tests/" | sha256sum | awk '{ print $1 }')" != "58d7d38903a42b7a92849784c596548a720888bd59f799c08f94523d59ae4164" ]]; then
    ret_status=$?
    echo "Output is other than expected"
    return $ret_status
  fi
}


@test "[$TESTSUITE] Check for user separation" {
  run ./occ -v duplicate:find-all -u tuser
  [ "$status" -eq 0 ]

  if [[ $(echo "$output" | grep "admin" | wc -l ) -ne 0 ]]; then
    ret_status=$?
    echo "Output is other than expected"
    return $ret_status
  fi
}
