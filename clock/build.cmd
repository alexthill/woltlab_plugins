rm com.alexthill.clock.tar
cd templates
tar cf ../templates.tar *.tpl
cd ..
tar cf com.alexthill.clock.tar *.xml templates.tar
rm templates.tar