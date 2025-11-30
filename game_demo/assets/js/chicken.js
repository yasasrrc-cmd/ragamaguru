let chickenState = {
    gameActive: false,
    betAmount: 0,
    currentMultiplier: 1.0,
    bonesCollected: 0,
    totalBones: 0,
    totalBombs: 0,
    revealedTiles: 0,
    grid: [],
    history: []
};

const GRID_SIZE = 25; // 5x5 grid

const difficultySettings = {
    easy: { bones: 8, bombs: 2 },
    medium: { bones: 6, bombs: 4 },
    hard: { bones: 4, bombs: 6 }
};

function initializeChickenGrid() {
    const grid = document.getElementById('chickenGrid');
    grid.innerHTML = '';
    
    for (let i = 0; i < GRID_SIZE; i++) {
        const tile = document.createElement('div');
        tile.className = 'chicken-tile';
        tile.dataset.index = i;
        tile.innerHTML = '🐔';
        tile.addEventListener('click', () => revealTile(i));
        grid.appendChild(tile);
    }
}

function startChickenGame() {
    const betAmount = parseFloat(document.getElementById('betAmount').value);
    const balance = parseFloat(document.getElementById('userBalance').textContent);
    
    if (betAmount <= 0 || betAmount > balance) {
        alert('Invalid bet amount!');
        return;
    }
    
    const difficulty = document.getElementById('difficulty').value;
    const settings = difficultySettings[difficulty];
    
    // Deduct bet amount
    updateBalance(balance - betAmount);
    
    // Initialize game state
    chickenState = {
        gameActive: true,
        betAmount: betAmount,
        currentMultiplier: 1.0,
        bonesCollected: 0,
        totalBones: settings.bones,
        totalBombs: settings.bombs,
        revealedTiles: 0,
        grid: generateChickenGrid(settings.bones, settings.bombs),
        history: chickenState.history
    };
    
    // Update UI
    document.getElementById('currentBet').textContent = '$' + betAmount.toFixed(2);
    document.getElementById('currentMultiplier').textContent = '1.00x';
    document.getElementById('potentialWin').textContent = '$' + betAmount.toFixed(2);
    document.getElementById('bonesCollected').textContent = '0';
    
    document.getElementById('startButton').disabled = true;
    document.getElementById('cashoutButton').disabled = false;
    document.getElementById('difficulty').disabled = true;
    
    initializeChickenGrid();
}

function generateChickenGrid(bones, bombs) {
    const grid = new Array(GRID_SIZE).fill('empty');
    const positions = [];
    
    // Generate random positions for bones
    while (positions.length < bones) {
        const pos = Math.floor(Math.random() * GRID_SIZE);
        if (!positions.includes(pos)) {
            positions.push(pos);
            grid[pos] = 'bone';
        }
    }
    
    // Generate random positions for bombs
    while (positions.length < bones + bombs) {
        const pos = Math.floor(Math.random() * GRID_SIZE);
        if (!positions.includes(pos)) {
            positions.push(pos);
            grid[pos] = 'bomb';
        }
    }
    
    return grid;
}

function revealTile(index) {
    if (!chickenState.gameActive) {
        alert('Start a new game first!');
        return;
    }
    
    const tile = document.querySelector(`[data-index="${index}"]`);
    if (tile.classList.contains('revealed')) {
        return;
    }
    
    const tileType = chickenState.grid[index];
    tile.classList.add('revealed');
    chickenState.revealedTiles++;
    
    if (tileType === 'bone') {
        tile.innerHTML = '🦴';
        tile.classList.add('bone');
        chickenState.bonesCollected++;
        
        // Calculate multiplier (increases with each bone)
        chickenState.currentMultiplier = 1 + (chickenState.bonesCollected * 0.5);
        
        const potentialWin = chickenState.betAmount * chickenState.currentMultiplier;
        
        document.getElementById('currentMultiplier').textContent = chickenState.currentMultiplier.toFixed(2) + 'x';
        document.getElementById('potentialWin').textContent = '$' + potentialWin.toFixed(2);
        document.getElementById('bonesCollected').textContent = chickenState.bonesCollected;
        
        // Check if all bones collected
        if (chickenState.bonesCollected === chickenState.totalBones) {
            setTimeout(() => {
                alert('Amazing! You found all bones! 🎉');
                cashOutChicken();
            }, 500);
        }
        
    } else if (tileType === 'bomb') {
        tile.innerHTML = '💣';
        tile.classList.add('bomb');
        
        setTimeout(() => {
            endChickenGame(false);
        }, 500);
    }
}

function cashOutChicken() {
    if (!chickenState.gameActive || chickenState.bonesCollected === 0) {
        return;
    }
    
    const winAmount = chickenState.betAmount * chickenState.currentMultiplier;
    
    submitChickenBet(true, winAmount);
    endChickenGame(true, winAmount);
}

async function submitChickenBet(won, winAmount) {
    const response = await fetch('api/place-chicken-bet.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            bet_amount: chickenState.betAmount,
            won: won,
            win_amount: won ? winAmount : 0,
            multiplier: chickenState.currentMultiplier,
            bones_collected: chickenState.bonesCollected
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        updateBalance(result.balance);
    }
}

function endChickenGame(won, winAmount = 0) {
    chickenState.gameActive = false;
    
    // Reveal all tiles
    const tiles = document.querySelectorAll('.chicken-tile');
    tiles.forEach((tile, index) => {
        tile.classList.add('disabled');
        if (!tile.classList.contains('revealed')) {
            const tileType = chickenState.grid[index];
            if (tileType === 'bomb') {
                tile.innerHTML = '💣';
                tile.classList.add('bomb', 'revealed');
            } else if (tileType === 'bone') {
                tile.innerHTML = '🦴';
                tile.classList.add('bone', 'revealed');
            }
        }
    });
    
    if (!won) {
        submitChickenBet(false, 0);
        alert('BOOM! You hit a bomb! 💣');
    } else {
        alert(`You won $${winAmount.toFixed(2)}! 🎉`);
    }
    
    addToChickenHistory(won, winAmount);
    
    document.getElementById('startButton').disabled = false;
    document.getElementById('cashoutButton').disabled = true;
    document.getElementById('difficulty').disabled = false;
}

function addToChickenHistory(won, amount) {
    chickenState.history.unshift({
        won: won,
        amount: amount,
        multiplier: chickenState.currentMultiplier,
        bones: chickenState.bonesCollected
    });
    
    if (chickenState.history.length > 10) {
        chickenState.history.pop();
    }
    
    const historyDiv = document.getElementById('chickenHistory');
    historyDiv.innerHTML = '';
    
    chickenState.history.forEach(game => {
        const item = document.createElement('div');
        item.className = 'history-item-chicken ' + (game.won ? 'win' : 'loss');
        item.innerHTML = `
            <div class="amount">${game.won ? '+' : '-'}$${Math.abs(game.amount).toFixed(2)}</div>
            <div class="multiplier">${game.multiplier.toFixed(2)}x | ${game.bones} bones</div>
        `;
        historyDiv.appendChild(item);
    });
}

function updateBalance(newBalance) {
    document.getElementById('userBalance').textContent = parseFloat(newBalance).toFixed(2);
}

// Wallet functions (same as aviator)
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

// Update start button text with bet amount
document.getElementById('betAmount')?.addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    document.getElementById('startButton').textContent = `Start Game ($${amount.toFixed(2)})`;
});

// Initialize grid on page load
initializeChickenGrid();