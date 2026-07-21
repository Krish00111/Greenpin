document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ GreenPin script loaded.");
  
    const mapContainer = document.getElementById("map");
    if (!mapContainer) {
      console.error("❌ Map container missing.");
      return;
    }
  
    const map = L.map("map").setView([20.5937, 78.9629], 5);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
    }).addTo(map);
  
    let marker = null; // This is for the "new" pin
    const suggestBox = document.getElementById("suggestList");
    

    const savedMarkers = new Map();
  
  
    async function loadSavedPins() {
      if (!LOGGED_IN) {
        console.log("Not logged in. Skipping pin load.");
        return;
      }
      try {
        const response = await fetch("get_pin.php");
        if (!response.ok) throw new Error("Failed to fetch pins.");
  
        const pins = await response.json();
  
        if (pins && pins.length > 0) {
          console.log(`Loading ${pins.length} saved pins...`);
          pins.forEach(pin => {
       
            const popupContent = `
              <b>Saved Pin</b><br>
              Suggested: ${pin.crop_suggestion || 'N/A'}<br>
              <button class="remove-pin-btn" data-pin-id="${pin.id}">Remove</button>
            `;
  
            const savedMarker = L.marker([pin.lat, pin.lng])
              .addTo(map)
              .bindPopup(popupContent);
              
            // Store the marker in our map so we can find it later to remove it
            savedMarkers.set(pin.id.toString(), savedMarker);
          });
        } else {
          console.log("No saved pins found for this user.");
        }
      } catch (error) {
        console.error("Error loading saved pins:", error);
      }
    }

    
    //REMOVE A PIN
    async function removePin(pinId, buttonElement) {
      if (!pinId) return;
      
      
      buttonElement.textContent = "Removing...";
      buttonElement.disabled = true;
  
      try {
        const response = await fetch("remove_pin.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: pinId })
        });
        
        const result = await response.json();
        
        if (result.ok) {
          //Find the marker using the ID
          const markerToRemove = savedMarkers.get(pinId.toString());
          if (markerToRemove) {
            //Remove it from the Leaflet map
            map.removeLayer(markerToRemove);
          }
          //Remove it from our internal tracking map
          savedMarkers.delete(pinId.toString());
        } else {
          alert("Error removing pin: " + result.error);
          buttonElement.textContent = "Remove";
          buttonElement.disabled = false;
        }
      } catch (error) {
        console.error("Error calling remove_pin.php:", error);
        buttonElement.textContent = "Remove";
        buttonElement.disabled = false;
      }
    }


    map.on('popupopen', (e) => {
      const popupNode = e.popup.getElement();
      const removeBtn = popupNode.querySelector(".remove-pin-btn");
      if (removeBtn) {
        removeBtn.addEventListener("click", (event) => {
          const pinId = event.target.dataset.pinId;
          removePin(pinId, event.target);
        });
      }
    });
   
  

    map.on("click", (e) => {
      const { lat, lng } = e.latlng;
      if (marker) map.removeLayer(marker); 
      marker = L.marker([lat, lng]).addTo(map); 
  
      document.getElementById("lat").value = lat;
      document.getElementById("lng").value = lng;
  
      suggestBox.innerHTML = `
        <p>📍 Location Selected: <b>${lat.toFixed(3)}, ${lng.toFixed(3)}</b></p>
        <p>Now select soil and water details below.</p>
      `;
    });
  
    // Get Suggestion Button
    const suggestBtn = document.getElementById("getSuggest");
    suggestBtn.addEventListener("click", async () => {
      const lat = document.getElementById("lat").value;
      const lng = document.getElementById("lng").value;
      if (!lat || !lng) {
        alert("Please click on the map to pin a location first!");
        return;
      }
      const soil = document.getElementById("soil").value;
      const water = document.getElementById("water").value;
      const irrigation = document.getElementById("irrigation").value;
      suggestBox.innerHTML = "<p>🔄 Getting suggestions...</p>";
  
      try {
        const response = await fetch("suggest.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            lat: lat, lng: lng, soil: soil, water: water, irrigation: irrigation,
          }),
        });
        if (!response.ok) throw new Error("Network response was not ok");
        const data = await response.json();
        let cropsHtml = "No suggestions found.";
        if (data.crops && data.crops.length > 0) {
          cropsHtml = data.crops
            .map(
              (crop) =>
                `<li><b>${crop.name}</b> (Score: ${crop.score})<br><small>${crop.reason}</small></li>`
            )
            .join("");
          cropsHtml = `<ul>${cropsHtml}</ul>`;
        }
        suggestBox.innerHTML = `
          <p><b>Suggestions for ${data.season} (Lat: ${parseFloat(data.lat).toFixed(2)}):</b></p>
          <p>Detected Climate: <b>${data.climate_detected}</b></p>
          ${cropsHtml}
          <p style="margin-top:10px; font-size: 0.8em; color: #555;">
            <b>Inputs:</b> Soil: ${soil}, Water: ${water}, Irrigation: ${irrigation}
          </p>
        `;
      } catch (error) {
        console.error("Fetch error:", error);
        suggestBox.innerHTML = `<p style="color:red;">❌ Error getting suggestions. Check console.</p>`;
      }
    });
  
    // save Pin Button mate
    const saveBtn = document.getElementById("savePinBtn");
    if (saveBtn) {
      saveBtn.addEventListener("click", async () => {
        const lat = document.getElementById("lat").value;
        const lng = document.getElementById("lng").value;
        if (!lat || !lng) {
          alert("Please select a location first!");
          return;
        }
        const firstCrop = suggestBox.querySelector("ul li b")?.textContent || "N/A";
        try {
          const res = await fetch("save_pin.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              lat: lat, lng: lng, suggestion: firstCrop,
            }),
          });
          const result = await res.json();
          if (result.ok) {
            alert("✅ Pin saved!");
            //badhi pin reaload kari navi batav va
      
            savedMarkers.forEach(marker => map.removeLayer(marker));
            savedMarkers.clear();
            loadSavedPins();
          } else {
            alert("❌ Error saving pin: " + result.error);
          }
        } catch (err) {
          console.error(err);
          alert("❌ Error saving pin.");
        }
      });
    }
  

    loadSavedPins();
    
    console.log("✅ GreenPin setup complete.");
  });