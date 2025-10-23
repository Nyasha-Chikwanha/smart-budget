// auth.js - simple localStorage based auth for demo
(function(){
  // helper: get users array
  function getUsers(){
    try {
      const raw = localStorage.getItem('sb_users');
      return raw ? JSON.parse(raw) : [];
    } catch(e){ return []; }
  }
  function saveUsers(users){
    localStorage.setItem('sb_users', JSON.stringify(users));
  }

  window.validateEmail = function(email){
    return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
  };

  window.signupUser = function({name,email,password}){
    if (!validateEmail(email)) return {success:false, message:'Invalid email.'};
    const users = getUsers();
    const exists = users.find(u => u.email === email);
    if (exists) return {success:false, message:'An account with that email already exists.'};
    const user = {
      id: Date.now(),
      name: name,
      email: email,
      password: password // NOTE: storing plain passwords is insecure. This is a demo.
    };
    users.push(user);
    saveUsers(users);
    // remember them (auto-login)
    localStorage.setItem('currentUser', email);
    return {success:true};
  };

  window.loginUser = function(email,password, remember){
    const users = getUsers();
    const user = users.find(u => u.email === email);
    if (!user) return {success:false, message:'No account with that email.'};
    if (user.password !== password) return {success:false, message:'Incorrect password.'};
    if (remember) {
      // set as current user (persistent)
      localStorage.setItem('currentUser', email);
    } else {
      // set currentUser for this session (still stored but user can logout)
      localStorage.setItem('currentUser', email);
    }
    return {success:true};
  };

  window.getCurrentUser = function(){
    const email = localStorage.getItem('currentUser');
    if (!email) return null;
    const users = getUsers();
    const user = users.find(u => u.email === email);
    return user || null;
  };

  window.logout = function(){
    localStorage.removeItem('currentUser');
  };
})();