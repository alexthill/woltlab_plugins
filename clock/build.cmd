rm -f com.xaver.clock.tar
cd templates
tar cf ../templates.tar *.tpl
cd ..
tar cf com.xaver.clock.tar *.xml templates.tar
rm templates.tar
