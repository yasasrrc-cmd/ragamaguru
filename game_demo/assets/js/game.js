let gameState = {
    isPlaying: false,
    currentMultiplier: 1.00,
    betPlaced: false,
    betAmount: 0,
    crashPoint: 0,
    history: []
};

let animationId;
let startTime;

function updateBalance(newBalance) {
    document.getElementById('userBalance').textContent = parseFloat(newBalance).toFixed(2);
}

function placeBet() {
    if (gameState.isPlaying) {
        alert('Wait for the next round!');
        return;
    }
    
    const betAmount = parseFloat(document.getElementById('betAmount').value);
    const balance = parseFloat(document.getElementById('userBalance').textContent);
    
    if (betAmount <= 0 || betAmount > balance) {
        alert('Invalid bet amount!');
        return;
    }
    
    gameState.betAmount = betAmount;
    gameState.betPlaced = true;
    
    document.getElementById('betButton').disabled = true;
    document.getElementById('cashoutButton').disabled = false;
    
    if (!gameState.isPlaying) {
        startGame();
    }
}

function cashOut() {
    if (!gameState.betPlaced || !gameState.isPlaying) return;
    
    const cashoutMultiplier = gameState.currentMultiplier;
    
    submitBet(cashoutMultiplier);
    
    gameState.betPlaced = false;
    document.getElementById('cashoutButton').disabled = true;
}

async function submitBet(cashoutMultiplier = null) {
    const response = await fetch('api/place-bet.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            bet_amount: gameState.betAmount,
            cashout_multiplier: cashoutMultiplier,
            crash_point: gameState.crashPoint
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        updateBalance(result.balance);
        
        if (result.status === 'won') {
            alert(`You won $${result.win_amount.toFixed(2)}!`);
        }
    }
}

function startGame() {
    gameState.isPlaying = true;
    gameState.currentMultiplier = 1.00;
    gameState.crashPoint = generateCrashPoint();
    
    document.getElementById('gameStatus').textContent = 'Flying...';
    document.getElementById('betButton').disabled = true;
    
    startTime = Date.now();
    animate();
}

function animate() {
    const elapsed = (Date.now() - startTime) / 1000;
    gameState.currentMultiplier = 1 + (elapsed * 0.5);
    
    document.getElementById('multiplier').textContent = gameState.currentMultiplier.toFixed(2) + 'x';
    
    if (gameState.currentMultiplier >= gameState.crashPoint) {
        endGame();
        return;
    }
    
    const autoCashout = parseFloat(document.getElementById('autoCashout').value);
    if (gameState.betPlaced && gameState.currentMultiplier >= autoCashout) {
        cashOut();
    }
    
    animationId = requestAnimationFrame(animate);
}

function endGame() {
    cancelAnimationFrame(animationId);
    gameState.isPlaying = false;
    
    document.getElementById('multiplier').textContent = gameState.crashPoint.toFixed(2) + 'x';
    document.getElementById('gameStatus').textContent = 'Crashed!';
    
    if (gameState.betPlaced) {
        submitBet(null);
        gameState.betPlaced = false;
    }
    
    addToHistory(gameState.crashPoint);
    
    document.getElementById('betButton').disabled = false;
    document.getElementById('cashoutButton').disabled = true;
    
    setTimeout(() => {
        document.getElementById('gameStatus').textContent = 'Starting new round...';
        setTimeout(startGame, 3000);
    }, 2000);
}

function generateCrashPoint() {
    const random = Math.random();
    if (random < 0.05) return 1 + Math.random() * 0.5;
    if (random < 0.3) return 1.5 + Math.random() * 1.5;
    if (random < 0.7) return 2 + Math.random() * 3;
    return 3 + Math.random() * 7;
}

function addToHistory(crashPoint) {
    gameState.history.unshift(crashPoint);
    if (gameState.history.length > 10) gameState.history.pop();
    
    const historyDiv = document.getElementById('history');
    historyDiv.innerHTML = '';
    
    gameState.history.forEach(point => {
        const item = document.createElement('div');
        item.className = 'history-item ' + (point < 2 ? 'crash-low' : 'crash-high');
        item.textContent = point.toFixed(2) + 'x';
        historyDiv.appendChild(item);
    });
}

async function deposit() {
    const amount = parseFloat(document.getElementById('depositAmount').value);
    
    if (amount <= 0) {
        alert('Invalid amount!');
        return;
    }
    
    const response = await fetch('api/deposit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({amount})
    });
    
    const result = await response.json();
    
    if (result.success) {
        updateBalance(result.balance);
        alert('Deposit successful!');
        closeWallet();
    } else {
        alert(result.message);
    }
}

async function withdraw() {
    const amount = parseFloat(document.getElementById('withdrawAmount').value);
    
    if (amount <= 0) {
        alert('Invalid amount!');
        return;
    }
    
    const response = await fetch('api/withdraw.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({amount})
    });
    
    const result = await response.json();
    
    if (result.success) {
        updateBalance(result.balance);
        alert('Withdrawal successful!');
        closeWallet();
    } else {
        alert(result.message);
    }
}

function showWallet() {
    document.getElementById('walletModal').style.display = 'block';
}

function closeWallet() {
    document.getElementById('walletModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('walletModal');
    if (event.target == modal) {
        closeWallet();
    }
}

// Auto-start game after 2 seconds
setTimeout(startGame, 2000);