const mysql = require('mysql2');

// Create a MySQL connection pool
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root', // your MySQL username
    password: 'password', // your MySQL password
    database: 'your_database_name'
});

module.exports = pool.promise();  // Using promise-based API for async/await support

const db = require('./db');

async function logAction(action, userId, username, studentId = null, details = null) {
    try {
        const [result] = await db.query(
            'INSERT INTO logs (action, user_id, username, student_id, details) VALUES (?, ?, ?, ?, ?)',
            [action, userId, username, studentId, details]
        );
        console.log('Log entry created:', result);
    } catch (error) {
        console.error('Error logging action:', error);
    }
}

module.exports = { logAction };

app.post('/login', async (req, res) => {
    const { username, password } = req.body;

    // Perform login validation (example)
    const user = await db.query('SELECT * FROM users WHERE username = ? AND password = ?', [username, password]);
    if (user.length > 0) {
        const userId = user[0].id;
        const username = user[0].username;

        // Log the login event
        await logAction('Login', userId, username);

        res.send('Login successful');
    } else {
        res.send('Invalid credentials');
    }
});

app.post('/register', async (req, res) => {
    const { username, password } = req.body;

    // Perform registration logic (e.g., store user in DB)
    const [result] = await db.query('INSERT INTO users (username, password) VALUES (?, ?)', [username, password]);
    const userId = result.insertId;

    // Log the registration event
    await logAction('Registration', userId, username);

    res.send('Registration successful');
});

app.post('/generate-qr', async (req, res) => {
    const userId = req.user.id;  // Assuming the user is authenticated
    const username = req.user.username;

    // Log the QR code generation event
    await logAction('QR Code Generated', userId, username);

    res.send('QR Code generated');
});

app.post('/edit-student', async (req, res) => {
    const { studentId, name, age } = req.body;
    const userId = req.user.id;  // Assuming the user is authenticated
    const username = req.user.username;

    // Update student info in DB (example)
    await db.query('UPDATE students SET name = ?, age = ? WHERE id = ?', [name, age, studentId]);

    // Log the student info edit event
    await logAction('Student Info Edited', userId, username, studentId);

    res.send('Student info updated');
});

app.get('/logs', async (req, res) => {
    try {
        const [logs] = await db.query('SELECT * FROM logs ORDER BY timestamp DESC');
        res.render('logs', { logs });  // Assuming you're using a view template like EJS or Handlebars
    } catch (error) {
        console.error('Error fetching logs:', error);
        res.status(500).send('Server error');
    }
});
