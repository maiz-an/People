import jsonfile from "jsonfile";
import moment from "moment";
import simpleGit from "simple-git";
import random from "random";

const path = "./data.json";
const git = simpleGit();

const makeCommits = (daysLeft) => {
  if (daysLeft === 0) return git.push();

  // Random intensity for the day (high or low)
  const commitsToday = random.boolean() ? random.int(1, 5) : random.int(5, 20);

  // Pick a date in the past 3 months
  const date = moment().subtract(random.int(0, 90), "days").format();

  const commit = (n) => {
    if (n === 0) return makeCommits(daysLeft - 1);

    const data = { date: date };

    jsonfile.writeFile(path, data, (err) => {
      if (err) return console.error("Error writing file:", err);

      git.add([path])
        .commit("Updated data.json", { "--date": date })
        .then(() => {
          console.log(`Commit on ${date}, remaining: ${n - 1}`);
          commit(n - 1);
        })
        .catch((err) => console.error("Error during git operation:", err));
    });
  };

  commit(commitsToday);
};

// Start making commits
makeCommits(50);
