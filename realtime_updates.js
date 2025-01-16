// Function to update notifications and messages dynamically
async function fetchUpdates() {
    try {
        const response = await fetch('fetch_updates.php');
        const data = await response.json();

        // Update notification icon based on unread notifications
        const notificationIcon = document.querySelector('.noti img');
        notificationIcon.src = data.unread_notifications > 0 ? 'notification.gif' : 'notification.png';

        // Update message icon based on unread messages
        const messageIcon = document.querySelector('.circle-button img');
        messageIcon.src = data.unread_messages > 0 ? 'talk.gif' : 'talk.png';

        // Update food items
        const foodGrid = document.querySelector('.grid');
        foodGrid.innerHTML = ''; // Clear current items
        data.food_items.forEach(item => {
            const foodItem = document.createElement('div');
            foodItem.classList.add('food-item');
            foodItem.innerHTML = `
                <img src="${item.food_image || 'https://via.placeholder.com/300x200?text=No+Image'}" alt="${item.food_title}">
                <div class="food-item-content">
                    <h3 class="hclss">${item.food_title}</h3>
                    <p>${item.description}</p>
                </div>
                <div class="button-container">
                    <a href="view_item.php?food_id=${item.id}&user_id=${item.user_id}" class="btn">
                        <span style="color: #4CAF50; font-weight: 700;">View </span> Details
                    </a>
                    <form action="request_handler.php" method="post">
                        <input type="hidden" name="food_id" value="${item.id}">
                        <input type="hidden" name="session_id" value="${id}">
                        <button type="submit" name="request_item" class="request-btn">
                            Request <span style="color: #4CAF50; font-weight: 700;"> this </span>
                        </button>
                    </form>
                </div>
                <div class="poster-info">
                    <span class="mini">by ${item.poster_name}</span>
                    <img src="${item.poster_pic}" alt="${item.poster_name}'s profile picture">
                </div>
            `;
            foodGrid.appendChild(foodItem);
        });
    } catch (error) {
        console.error('Error fetching updates:', error);
    }
}

// Fetch updates every 10 seconds
setInterval(fetchUpdates, 10000);
fetchUpdates(); // Initial call to fetch updates when page loads
