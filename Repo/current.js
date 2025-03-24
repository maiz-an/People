import jsonfile from "jsonfile";
import moment from "moment";
import simpleGit from "simple-git";
import random from "random";

const path = "./data.json";

const makeCommits = (n) => {
  if (n === 0) return simpleGit().push();

  const startOfYear = moment().startOf("year"); // January 1st
  const yesterday = moment().subtract(1, "d"); // Yesterday

  const randomDays = random.int(0, yesterday.diff(startOfYear, "days")); // Random day within this year

  const date = startOfYear.add(randomDays, "d").format(); // Get final random date

  const data = { date: date };

  jsonfile.writeFile(path, data, (err) => {
    if (err) {
      console.error("Error writing file:", err);
      return;
    }

    const git = simpleGit();

    git.add([path])
      .commit("Updated data.json", { "--date": date })
      .then(() => {
        console.log(`Commit ${n} done on ${date}`);
        makeCommits(n - 1); // Recursive call
      })
      .catch((err) => console.error("Error during git operation:", err));
  });
};

// Call the function correctly
makeCommits(50);
