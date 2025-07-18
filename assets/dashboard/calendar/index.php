<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/qr/assets/dashboard/calendar/style.css">
    <title>Document</title>
</head>

<body>
    <div class="my-calendar">
        <div class="calendar">
            <div class="calendar__header">
                <span class="month-picker" id="month-picker"> May </span>
                <div class="year-picker" id="year-picker">
                    <span class="year-change" id="pre-year">
                        <pre><</pre>
                    </span>
                    <span id="year">2020 </span>
                    <span class="year-change" id="next-year">
                        <pre>></pre>
                    </span>
                </div>
            </div>

            <div class="calendar-body">
                <div class="calendar-week-days">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
                <div class="calendar-days">
                </div>
            </div>
            <div class="calendar-footer">
                <label for="notes" class="notes">Notes</label>
                <button class="add-note" id="add-note">Add Note</button>
            </div>
            <div class="date-time-formate">
                <div class="day-text-formate">TODAY</div>
                <div class="date-time-value">
                    <div class="time-formate">02:51:20</div>
                    <div class="date-formate">23 - July - 2022</div>
                </div>
            </div>
            <div class="month-list"></div>
        </div>
    </div>

    <script src="/qr/assets/dashboard/calendar/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthPicker = document.querySelector('.my-calendar .month-picker');
            const monthList = document.querySelector('.my-calendar .month-list');

            monthPicker.addEventListener('click', function() {
                monthList.classList.toggle('show');
                monthList.classList.toggle('hideonce');
            });

            
            document.addEventListener('click', function(e) {
                if (!monthPicker.contains(e.target) && !monthList.contains(e.target)) {
                    monthList.classList.remove('show');
                    monthList.classList.add('hideonce');
                }
            });
        });
    </script>
</body>

</html>