rm -f com.xaver.electionbot.tar
cd files_wbb
tar cf ../files_wbb.tar *
cd ../templates_wbb
tar cf ../templates_wbb.tar *
cd ..
tar cf com.xaver.electionbot.tar *.xml files_wbb.tar templates_wbb.tar language/*
rm files_wbb.tar
rm templates_wbb.tar
