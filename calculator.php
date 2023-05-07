<?php
// Retrieve information from feeds
$feed_1 = json_decode(file_get_contents('https://whattomine.com/coins.json'), true)['coins'];
$feed_2 = json_decode(file_get_contents('https://api2.nicehash.com/main/api/v2/public/simplemultialgo/info'), true)['miningAlgorithms'];
$currency = json_decode(file_get_contents('https://bitpay.com/api/rates'), true);

// Calculate estimated revenue for each coin
$revenues = array();
foreach ($feed_1 as $coin_info) {
    $revenue_per_block = ($coin_info['block_reward'] * $coin_info['exchange_rate']) / $coin_info['block_time'];
    $blocks_per_day = 86400 / $coin_info['block_time'];
    $blocks_per_month = $blocks_per_day * 30;
    $daily_revenue_usd = $revenue_per_block * $blocks_per_day;
    $monthly_revenue_usd = $revenue_per_block * $blocks_per_month;
    $daily_revenue_btc = $daily_revenue_usd * $coin_info['exchange_rate'];
    $monthly_revenue_btc = $monthly_revenue_usd * $coin_info['exchange_rate'];
    $revenues[$coin_info['tag']] = array(
        'daily_usd' => $daily_revenue_usd,
        'monthly_usd' => $monthly_revenue_usd,
        'daily_btc' => $daily_revenue_btc,
        'monthly_btc' => $monthly_revenue_btc
    );
}

// Calculate estimated payout for each algorithm
$payouts = array();
foreach ($feed_2 as $algo_info) {
    $daily_payout_btc = $algo_info['speed'] * $algo_info['paying'] * 86400;
    $monthly_payout_btc = $daily_payout_btc * 30;
    $payouts[$algo_info['algorithm']] = array(
        'daily_btc' => $daily_payout_btc,
        'monthly_btc' => $monthly_payout_btc
    );
}
?>
<script>
function displayData(hashrate, powerConsumption, electricityCost) {
  // Get JSON data from feeds
  var feed1 = <?php echo json_encode($feed_1); ?>;
  var feed2 = <?php echo json_encode($feed_2); ?>;
  var exchangeRates = <?php echo json_encode($currency); ?>;
  
  // Extract ZAR rate from exchange rates feed
  var zarRate = exchangeRates.find(function(rate) {
    return rate.code === "ZAR";
  }).rate;

  // Create table header
  var table = "<table><tr><th>Coin</th><th>Algorithm</th><th>Profitability (BTC/day)</th><th>Profitability (ZAR/day)</th><th>Electricity Consumption</th><th>Electricity Cost</th><th>Total Profitability (ZAR/month)</th></tr>";

  // Iterate over coins in feed 1
  for (var coin in feed1) {
    // Get relevant data for coin
    var coinData = feed1[coin];
    var coinName = coin;
    var algorithm = coinData.algorithm;
    var estimatedRewards = parseFloat(coinData.estimated_rewards);
    var btcRevenue = parseFloat(coinData.btc_revenue24);

    // Calculate profitability based on user input
    var profitabilityBTC = (btcRevenue * hashrate / 100);
    var profitabilityZAR = profitabilityBTC * zarRate;
    var profitabilityZARPerMonth = profitabilityZAR * 30.4;

    // Calculate electricity consumption and cost
    var electricityConsumption = powerConsumption;
    var electricityCostPerMonth = (((powerConsumption/1000)*electricityCost)*24*30.4);

    // Calculate total profitability
    var totalProfitability = profitabilityZARPerMonth - electricityCostPerMonth;

    // Add row to table
    table += "<tr><td>" + coinName + "</td><td>" + algorithm + "</td><td>" + profitabilityBTC.toFixed(8) + "</td><td>" + profitabilityZAR.toFixed(2) + "</td><td>" + electricityConsumption + "</td><td>" + electricityCostPerMonth.toFixed(2) + "</td><td>" + totalProfitability.toFixed(2) + "</td></tr>";
  }

  // Iterate over algorithms in feed 2
  for (var i = 0; i < feed2.length; i++) {
    // Get relevant data for algorithm
    var algorithmData = feed2[i];
    var algorithm = algorithmData.algorithm;
    var title = algorithmData.title;
    var paying = parseFloat(algorithmData.paying);	
	  
	// Convert hashrate to MH/s
  	if (paying > 0.00009) {
  	  var newhashrate = hashrate / 1000000;
	  var issol = "(Unsure of hash calculation.)";
  	} else {
		var newhashrate = hashrate;
		var issol = "";
	}

	console.log("Paying: " + paying + " Algo: " + algorithm + " Hashrate: " + newhashrate);
  	// Calculate profitability in BTC per month
  var profitabilityBTC = (paying * newhashrate / 100);
	var profitabilityZAR = profitabilityBTC * zarRate;
	var profitabilityZARPerMonth = profitabilityZAR * 30.4;
	var electricityConsumption = powerConsumption;
	var consumptionPrice = (((powerConsumption/1000)*electricityCost)*24*30.4);
	var totalProfitability = profitabilityZARPerMonth - consumptionPrice;
	// Add row to table
	table += "<tr><td>" + title + "</td><td>" + algorithm + "</td><td>" + profitabilityBTC + "</td><td>" + profitabilityZAR.toFixed(2) + "</td><td>" + electricityConsumption + "</td><td>" + consumptionPrice.toFixed(2) + "</td><td>" + totalProfitability.toFixed(2) + " " + issol + "</td></tr>";
	}
	
	// Close table
	table += "</table>";
	
	// Display table
	document.getElementById("tableContainer").innerHTML = table;
}
</script>
<form id="profitabilityForm">
  <label for="hashrate">Hashrate:</label>
  <input type="number" id="hashrate" name="hashrate" step="0.01" required>

  <label for="powerConsumption">Power Consumption (W):</label>
  <input type="number" id="powerConsumption" name="powerConsumption" required>

  <label for="electricityCost">Electricity Cost (ZAR/kWh):</label>
  <input type="number" id="electricityCost" name="electricityCost" step="0.01" required>

  <button type="submit">Submit</button>
</form>

<div id="tableContainer"></div>

<script>
function calculateProfitability() {
  var hashrate = parseFloat(document.getElementById("hashrate").value);
  var powerConsumption = parseFloat(document.getElementById("powerConsumption").value);
  var electricityCost = parseFloat(document.getElementById("electricityCost").value);
  displayData(hashrate, powerConsumption, electricityCost);
}

document.getElementById("profitabilityForm").addEventListener("submit", function(event) {
  event.preventDefault();
  calculateProfitability();
});
</script>
