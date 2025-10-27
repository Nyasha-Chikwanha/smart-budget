// js/form-handlers.js
// Centralized form handlers for Smart Budget App

const FormHandlers = {
    // Add new income with user_id
    addNewIncome: function(incomeData) {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) {
            alert('Please log in first');
            window.location.href = 'login.html';
            return false;
        }

        try {
            const newIncome = {
                ...incomeData,
                user_id: currentUser.id || currentUser.user_id,
                id: Date.now().toString(),
                createdAt: new Date().toISOString()
            };

            const allIncome = JSON.parse(localStorage.getItem('incomeData') || '[]');
            allIncome.push(newIncome);
            localStorage.setItem('incomeData', JSON.stringify(allIncome));
            
            alert('Income added successfully!');
            window.location.href = 'dashboard.html';
            return true;
        } catch (error) {
            console.error('Error adding income:', error);
            alert('Error adding income. Please try again.');
            return false;
        }
    },

    // Add new expense with user_id
    addNewExpense: function(expenseData) {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) {
            alert('Please log in first');
            window.location.href = 'login.html';
            return false;
        }

        try {
            const newExpense = {
                ...expenseData,
                user_id: currentUser.id || currentUser.user_id,
                id: Date.now().toString(),
                createdAt: new Date().toISOString()
            };

            const allExpenses = JSON.parse(localStorage.getItem('expenseData') || '[]');
            allExpenses.push(newExpense);
            localStorage.setItem('expenseData', JSON.stringify(allExpenses));
            
            alert('Expense added successfully!');
            window.location.href = 'dashboard.html';
            return true;
        } catch (error) {
            console.error('Error adding expense:', error);
            alert('Error adding expense. Please try again.');
            return false;
        }
    },

    // Add new goal with user_id
    addNewGoal: function(goalData) {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) {
            alert('Please log in first');
            window.location.href = 'login.html';
            return false;
        }

        try {
            const newGoal = {
                ...goalData,
                user_id: currentUser.id || currentUser.user_id,
                id: Date.now().toString(),
                createdAt: new Date().toISOString(),
                current: 0,
                progress: 0
            };

            const allGoals = JSON.parse(localStorage.getItem('goalsData') || '[]');
            allGoals.push(newGoal);
            localStorage.setItem('goalsData', JSON.stringify(allGoals));
            
            alert('Goal created successfully!');
            window.location.href = 'dashboard.html';
            return true;
        } catch (error) {
            console.error('Error adding goal:', error);
            alert('Error creating goal. Please try again.');
            return false;
        }
    },

    // Update existing goal progress
    updateGoalProgress: function(goalId, additionalAmount) {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) return false;

        try {
            const allGoals = JSON.parse(localStorage.getItem('goalsData') || '[]');
            const goalIndex = allGoals.findIndex(goal => 
                goal.id === goalId && goal.user_id == currentUser.id
            );

            if (goalIndex !== -1) {
                allGoals[goalIndex].current = parseFloat(allGoals[goalIndex].current || 0) + parseFloat(additionalAmount);
                allGoals[goalIndex].progress = (allGoals[goalIndex].current / allGoals[goalIndex].target) * 100;
                localStorage.setItem('goalsData', JSON.stringify(allGoals));
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error updating goal:', error);
            return false;
        }
    }
};