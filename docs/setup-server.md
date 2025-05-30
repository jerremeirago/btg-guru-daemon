To fetch data from the AFL via command-line:

@INFO: This should be run in a cron job, see ./cron/daemon.sh
```bash
php artisan api:afl
```

To broadcast new data:

```bash
php artisan broadcast:afl-data
```

Then send it to queue:
php artisan queue:listen

To start reverb
php artisan reverb:start


add this to cron job
this will execute every 15 seconds
* * * * * /home/yourusername/whoami_script.sh



For Larave Echo:
```javascript
// In your WebSocket client
echo.channel('sports.live.afl')
    .listen('.afl.update', (data) => {
        console.log('Received AFL update notification', data);
        
        // If full data is needed, fetch it via API
        if (data.data_available) {
            fetchFullData();
        }
    });

// Function to fetch full data when needed
function fetchFullData() {
    fetch('/api/v1/live/afl')
        .then(response => response.json())
        .then(fullData => {
            console.log('Full AFL data:', fullData);
        });
}
```