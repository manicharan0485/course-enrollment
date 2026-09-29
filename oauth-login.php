<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login with OAuth - Course Enrollment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 50px 40px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 0;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 40px;
            cursor: pointer;
            color: #667eea;
            font-size: 18px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #999;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .divider::before {
            margin-right: 10px;
        }

        .divider::after {
            margin-left: 10px;
        }

        .oauth-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn-oauth {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-google {
            color: #333;
        }

        .btn-google:hover {
            border-color: #4285f4;
            background: #f1f5ff;
        }

        .btn-github {
            color: #333;
        }

        .btn-github:hover {
            border-color: #333;
            background: #f6f8fa;
        }

        .oauth-icon {
            font-size: 20px;
        }

        .toggle-auth {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .toggle-auth a {
            color: #667eea;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }

        .toggle-auth a:hover {
            text-decoration: underline;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #c33;
        }

        .error.show {
            display: block;
        }

        .loading {
            display: none;
            text-align: center;
            color: #667eea;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: #f0f0f0;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #999;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Course Enrollment</h1>
            <p>Login or Signup to continue</p>
        </div>

        <div class="tab-buttons">
            <button class="tab-btn active" onclick="switchTab('login')">Login</button>
            <button class="tab-btn" onclick="switchTab('signup')">Signup</button>
        </div>

        <div id="error" class="error"></div>
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Processing...</p>
        </div>

        <!-- LOGIN TAB -->
        <div id="login" class="tab-content active">
            <div class="oauth-buttons">
                <button class="btn-oauth btn-google" onclick="loginWithGoogle()">
                    <span class="oauth-icon">🔵</span>
                    <span>Google</span>
                </button>
                <button class="btn-oauth btn-github" onclick="loginWithGithub()">
                    <span class="oauth-icon">⚫</span>
                    <span>GitHub</span>
                </button>
            </div>

            <div class="divider">OR</div>

            <form onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <div class="password-container">
                        <input type="password" id="login-password" required placeholder="••••••••">
                        <span class="toggle-password" onclick="togglePassword('login-password')">👁️</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Login</span>
                </button>
            </form>
        </div>

        <!-- SIGNUP TAB -->
        <div id="signup" class="tab-content">
            <div class="oauth-buttons">
                <button class="btn-oauth btn-google" onclick="signupWithGoogle()">
                    <span class="oauth-icon">🔵</span>
                    <span>Google</span>
                </button>
                <button class="btn-oauth btn-github" onclick="signupWithGithub()">
                    <span class="oauth-icon">⚫</span>
                    <span>GitHub</span>
                </button>
            </div>

            <div class="divider">OR</div>

            <form onsubmit="handleSignup(event)">
                <div class="form-group">
                    <label for="signup-name">Full Name</label>
                    <input type="text" id="signup-name" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <div class="password-container">
                        <input type="password" id="signup-password" required placeholder="••••••••" minlength="12">
                    </div>
                    <small style="color: #999; display: block; margin-top: 5px;">Min 12 characters</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Create Account</span>
                </button>
            </form>
        </div>

        <div class="toggle-auth">
            <span id="toggle-text">Don't have an account? <a onclick="switchTab('signup')">Signup</a></span>
        </div>
    </div>

    <script>
        const API_URL = '/api/auth.php';

        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
            
            if (tab === 'login') {
                document.getElementById('toggle-text').innerHTML = 'Don\'t have an account? <a onclick="switchTab(\'signup\')">Signup</a>';
            } else {
                document.getElementById('toggle-text').innerHTML = 'Already have an account? <a onclick="switchTab(\'login\')">Login</a>';
            }
        }

        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function showError(msg) {
            const error = document.getElementById('error');
            error.textContent = msg;
            error.classList.add('show');
            setTimeout(() => error.classList.remove('show'), 5000);
        }

        function setLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        async function handleLogin(e) {
            e.preventDefault();
            setLoading(true);

            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            try {
                const res = await fetch(`${API_URL}?path=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await res.json();

                if (data.success) {
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    window.location.href = '/dashboard.php';
                } else {
                    showError(data.error || 'Login failed');
                }
            } catch (err) {
                showError('Network error: ' + err.message);
            } finally {
                setLoading(false);
            }
        }

        async function handleSignup(e) {
            e.preventDefault();
            setLoading(true);

            const full_name = document.getElementById('signup-name').value;
            const email = document.getElementById('signup-email').value;
            const password = document.getElementById('signup-password').value;

            try {
                const res = await fetch(`${API_URL}?path=signup`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ full_name, email, password })
                });

                const data = await res.json();

                if (data.success) {
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    window.location.href = '/dashboard.php';
                } else {
                    showError(data.error || 'Signup failed');
                }
            } catch (err) {
                showError('Network error: ' + err.message);
            } finally {
                setLoading(false);
            }
        }

        async function loginWithGoogle() {
            setLoading(true);
            try {
                const res = await fetch(`${API_URL}?path=google/authorize`, { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.authorization_url;
                } else {
                    showError('Failed to get Google authorization URL');
                    setLoading(false);
                }
            } catch (err) {
                showError('Error: ' + err.message);
                setLoading(false);
            }
        }

        async function loginWithGithub() {
            setLoading(true);
            try {
                const res = await fetch(`${API_URL}?path=github/authorize`, { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.authorization_url;
                } else {
                    showError('Failed to get GitHub authorization URL');
                    setLoading(false);
                }
            } catch (err) {
                showError('Error: ' + err.message);
                setLoading(false);
            }
        }

        async function signupWithGoogle() {
            await loginWithGoogle();
        }

        async function signupWithGithub() {
            await loginWithGithub();
        }
    </script>
</body>
</html>
