rm com.example.test.tar
cd files
tar cf ../files.tar *
cd ../templates
tar cf ../templates.tar *.tpl
cd ..
tar cf com.example.test.tar *.xml templates.tar files.tar
rm files.tar
rm templates.tar