export OC_PASS="test3588347"

evaluateHashResult(){
  expectedHash="$1"
  realHash="$(echo "$3" | sha256sum | awk '{ print $1 }')"
  if [[ $(echo "$3" | grep "file_hash" | wc -l ) -ne $2
      || "${realHash}" != "${expectedHash}" ]]; then
{
    echo "Output is other than expected:"
    echo "Count: $(echo "$3" | grep "file_hash" | wc -l )/$2"
    echo "Exepected: ${expectedHash}"
    echo "Result:    ${realHash}"
    echo "$3"
} > ${BATS_TEST_NUMBER}_${BATS_TEST_NAME}.out
    return 1
  fi
}

randomString() {
    cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w ${1:-32} | head -n 1
}
