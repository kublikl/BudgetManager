let categoryField = document.querySelector("#category");
let dateField = document.querySelector("#transactionDate");
let amountField = document.querySelector("#amountField");


categoryField.addEventListener("change", async () => {
  eventsAction();
})

dateField.addEventListener("change", async () => {
  eventsAction();
})

amountField.addEventListener("input", async () => {
  eventsAction();
})

const eventsAction = async () => {
  let categoryFieldValue = categoryField.value;
  let dateFieldValue = dateField.value;
  let amountFieldValue = amountField.value;

  if (categoryFieldValue) {
      renderInfoBox(categoryFieldValue);
  }

  if (categoryFieldValue && dateFieldValue) {
      renderExpenseBox(categoryFieldValue, dateFieldValue);
  }

  if (categoryFieldValue && dateFieldValue && amountFieldValue) {
      renderLeftBox(categoryFieldValue, dateFieldValue, amountFieldValue);
  }
}

const getLimitForCategory = async (id) => {
  try {
      const res = await fetch(`/api/limit/${id}`);
      const data = await res.json();
      return data;
  } catch (e) {
      console.log(`ERROR`, e);
  }
}

const getTotalForCategory = async (id, date) => {
  try {
      const res = await fetch(`/api/total/${id}?date=${date}`);
      const data = await res.json();
      return data;
  } catch (e) {
      console.log(`ERROR`, e);
  }
}

const renderInfoBox = async (categoryFieldValue) => {
  try {
      const result = await getLimitForCategory(categoryFieldValue);
      let limitInfo = '';
      if (result === null) {
          limitInfo = `This category has no limit.\n`;
      } else {
          limitInfo = `This category has a monthly amount limit ${result} .`;
      }     
      document.querySelector("#limit-info").innerHTML = limitInfo;
  } catch (e) {
      console.log(`ERROR`, e);
  }
}

const renderExpenseBox = async (categoryFieldValue, dateFieldValue) => {
  try {
      const result = await getTotalForCategory(categoryFieldValue, dateFieldValue);
      if (result) {
          document.querySelector("#expense-in-current-month").innerHTML = `${result} `;
      } else {
          document.querySelector("#expense-in-current-month").innerHTML = "You have no expenses for the month.";
      }
      
  } catch (e) {
      console.log(`ERROR`, e);
  }
}

const renderLeftBox = async (categoryFieldValue, dateFieldValue, amountFieldValue) => {
  try {
      let limit;
      let total;

      if (categoryFieldValue) {
          limit = await getLimitForCategory(categoryFieldValue);
      }
      
      if (categoryFieldValue && dateFieldValue) {
          total = await getTotalForCategory(categoryFieldValue, dateFieldValue);
      }

      let cashLeft = limit - total - amountFieldValue;

      console.log('cashLeft:', cashLeft);
          
      if (limit == "0.00") {
          document.querySelector("#cash-left").innerHTML = "No limit assigned to a category.";
          document.querySelector("#cash-left").style.setProperty("color", "rgba(33, 37, 41, 0.75)", "important");
      } else if (cashLeft < 0) {
          document.querySelector("#cash-left").innerHTML = `${cashLeft.toFixed(2)}`;
          document.querySelector("#cash-left").style.setProperty("color", "red", "important");
       } else if (cashLeft === 0) {
            document.querySelector("#cash-left").innerHTML = "Balance is zero.";
            document.querySelector("#cash-left").style.setProperty("color", "rgba(173, 216, 230, 0.75)", "important");
        } else if (cashLeft) {
          document.querySelector("#cash-left").innerHTML = `${cashLeft.toFixed(2)}`;
          document.querySelector("#cash-left").style.setProperty("color", "rgba(102, 255, 102, 0.75)", "important");
      }
      
  } catch (e) {
      console.log(`ERROR`, e);
  }
}