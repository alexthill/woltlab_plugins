rm -f com.alexthill.notifications.tar
cd files_calendar
tar cf ../files_calendar.tar *
cd ..
tar cf com.alexthill.notifications.tar *.xml files_calendar.tar language/*
rm files_calendar.tar
