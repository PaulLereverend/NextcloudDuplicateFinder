load helper.sh

#Remove old tests files and welcome files
rm -rf data/{admin,tuser}/files/*
./occ app:disable duplicatefinder
# Select a password that is random enough

if [[ -z $(./occ user:list | grep "tuser:") ]]; then
  ./occ user:add --password-from-env  --display-name="Test User" tuser
fi

createTestFiles "$MAX_FILES" 'data/admin/files/tests' 'data/tuser/files/tests/' 'data/tuser/files/tests2'

./occ app:enable duplicatefinder