rm -f com.xaver.notifications.tar
cd files_calendar
tar cf ../files_calendar.tar *
cd ..
tar cf com.xaver.notifications.tar *.xml files_calendar.tar language/*
rm files_calendar.tar
