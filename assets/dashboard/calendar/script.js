const isLeapYear = (year) => {
  return (
    (year % 4 === 0 && year % 100 !== 0) ||
    (year % 100 === 0 && year % 400 === 0)
  );
};

const getFebDays = (year) => {
  return isLeapYear(year) ? 29 : 28;
};

let calendar = document.querySelector('.calendar');
const month_names = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

let month_picker = document.querySelector('#month-picker');
const dayTextFormate = document.querySelector('.day-text-formate');
const timeFormate = document.querySelector('.time-formate');
const dateFormate = document.querySelector('.date-formate');

let notesStorage = JSON.parse(localStorage.getItem('calendarNotes')) || {};

const generateCalendar = (month, year) => {
  let calendar_days = document.querySelector('.calendar-days');
  calendar_days.innerHTML = '';  
  let calendar_header_year = document.querySelector('#year');
  let days_of_month = [31, getFebDays(year), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

  let currentDate = new Date();

  month_picker.innerHTML = month_names[month];
  calendar_header_year.innerHTML = year;

  let first_day = new Date(year, month);
  for (let i = 0; i < days_of_month[month] + first_day.getDay(); i++) {
    let day = document.createElement('div');

    if (i >= first_day.getDay()) {
      const dayNumber = i - first_day.getDay() + 1;
      day.innerHTML = dayNumber;
      day.classList.add('calendar-day');

      if (currentDate.getDate() === dayNumber && currentDate.getMonth() === month && currentDate.getFullYear() === year) {
        day.classList.add('current-date');

        const dateKey = `${year}-${month + 1}-${dayNumber}`;
        const existingNote = notesStorage[dateKey] || '';
        if (existingNote) {
          document.querySelector('.date-formate').textContent = `${dayNumber} - ${month_names[month]} - ${year}`;
          const footer = document.querySelector('.calendar-footer');
          footer.innerHTML = `
            <label for="notes">Notes</label>
            <div class="saved-note">${existingNote}</div>
            <button class="edit-note">Edit Note</button>
          `;
          document.querySelector('.edit-note').addEventListener('click', () => {
            footer.innerHTML = `
              <label for="notes">Edit Note</label>
              <textarea class="note-input" placeholder="Edit your note..." data-date="${dateKey}">${existingNote}</textarea>
              <button class="save-note">Save Note</button>
            `;
            document.querySelector('.note-input').focus();
            document.querySelector('.save-note').addEventListener('click', () => {
              const noteText = document.querySelector('.note-input').value;
              notesStorage[dateKey] = noteText;
              localStorage.setItem('calendarNotes', JSON.stringify(notesStorage));
              footer.innerHTML = `
                <label for="notes">Notes</label>
                <div class="saved-note">${noteText}</div>
              `;
              alert("Note saved!");
            });
          });
        }
      }

      day.addEventListener('click', () => {
        const dateKey = `${year}-${month + 1}-${dayNumber}`;
        const existingNote = notesStorage[dateKey] || '';
        document.querySelector('.date-formate').textContent = `${dayNumber} - ${month_names[month]} - ${year}`;
        const footer = document.querySelector('.calendar-footer');
        footer.innerHTML = `
          <label for="notes">Notes</label>
          <div class="saved-note">${existingNote || 'No note yet.'}</div>
        `;

        if (existingNote) {
          footer.innerHTML += `<button class="edit-note">Edit Note</button>`;
          document.querySelector('.edit-note').addEventListener('click', () => {
            footer.innerHTML = `
              <label for="notes">Edit Note</label>
              <textarea class="note-input" placeholder="Edit your note..." data-date="${dateKey}">${existingNote}</textarea>
              <button class="save-note">Save Note</button>
            `;
            document.querySelector('.note-input').focus();
            document.querySelector('.save-note').addEventListener('click', () => {
              const noteText = document.querySelector('.note-input').value;
              notesStorage[dateKey] = noteText;
              localStorage.setItem('calendarNotes', JSON.stringify(notesStorage));
              footer.innerHTML = `
                <label for="notes">Notes</label>
                <div class="saved-note">${noteText}</div>
              `;
              alert("Note saved!");
            });
          });
        } else {
          footer.innerHTML += `<button class="add-note">Add Note</button>`;
          document.querySelector('.add-note').addEventListener('click', () => {
            footer.innerHTML = `
              <label for="notes">Notes</label>
              <textarea class="note-input" placeholder="Add your note..." data-date="${dateKey}"></textarea>
              <button class="save-note">Save Note</button>
            `;
            document.querySelector('.note-input').focus();
            document.querySelector('.save-note').addEventListener('click', () => {
              const noteText = document.querySelector('.note-input').value;
              notesStorage[dateKey] = noteText;
              localStorage.setItem('calendarNotes', JSON.stringify(notesStorage));
              footer.innerHTML = `
                <label for="notes">Notes</label>
                <div class="saved-note">${noteText}</div>
              `;
              alert("Note saved!");
            });
          });
        }
      });
    }
    calendar_days.appendChild(day);
  }
};

let currentDate = new Date();
let currentMonth = { value: currentDate.getMonth() };
let currentYear = { value: currentDate.getFullYear() };
generateCalendar(currentMonth.value, currentYear.value);

document.querySelector('#pre-year').onclick = () => {
  --currentYear.value;
  generateCalendar(currentMonth.value, currentYear.value);
};

document.querySelector('#next-year').onclick = () => {
  ++currentYear.value;
  generateCalendar(currentMonth.value, currentYear.value);
};

let month_list = calendar.querySelector('.month-list');
month_names.forEach((e, index) => {
  let month = document.createElement('div');
  month.innerHTML = `<div>${e}</div>`;

  month_list.append(month);
  month.onclick = () => {
    currentMonth.value = index;
    generateCalendar(currentMonth.value, currentYear.value);
    month_list.classList.replace('show', 'hide');
    dayTextFormate.classList.remove('hideTime');
    dayTextFormate.classList.add('showtime');
    timeFormate.classList.remove('hideTime');
    timeFormate.classList.add('showtime');
    dateFormate.classList.remove('hideTime');
    dateFormate.classList.add('showtime');
  };
});

(function () {
  month_list.classList.add('hideonce');
})();

const todayShowTime = document.querySelector('.time-formate');
const todayShowDate = document.querySelector('.date-formate');

const currshowDate = new Date();
const showCurrentDateOption = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
const currentDateFormate = new Intl.DateTimeFormat('en-US', showCurrentDateOption).format(currshowDate);
todayShowDate.textContent = currentDateFormate;

setInterval(() => {
  const timer = new Date();
  const option = { hour: 'numeric', minute: 'numeric', second: 'numeric' };
  const formateTimer = new Intl.DateTimeFormat('en-us', option).format(timer);
  todayShowTime.textContent = formateTimer;
}, 1000);