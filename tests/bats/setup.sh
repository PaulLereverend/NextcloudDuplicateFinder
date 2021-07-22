load helper.sh

#Remove old tests files and welcome files
rm -rf data/{admin,tuser}/files/*
./occ app:disable duplicatefinder
# Select a password that is random enough

if [[ -z $(./occ user:list | grep "tuser:") ]]; then
  ./occ user:add --password-from-env  --display-name="Test User" tuser
fi

mkdir -p data/{admin,tuser}/files/tests data/tuser/files/tests2/
#Include 0 to have one file per user (edge case where duplicate exist only because of one file per user)
for i in $(seq 0 25)
do
  for j in $(seq 0 $i)
  do
    echo $i > data/admin/files/tests/${i}_${j}.txt
    echo $i > data/tuser/files/tests/${i}_${j}.txt
    echo $i > data/tuser/files/tests2/${i}_${j}.txt
  done
done

if [ -z "$DO_NOT_SCAN" ]; then
    ./occ files:scan --all
fi
./occ app:enable duplicatefinder
