import jsonfile from "jsonfile";
import moment from "moment";
import simpleGit from "simple-git";
import random from "random";

const path = "./data.json";

const makeCommits = (n) => {
  if (n === 0) return simpleGit().push();

  const x = random.int(0, 54);
  const y = random.int(0, 6);
  const date = moment().subtract(1, "y").add(1, "d").add(x, "w").add(y, "d").format();

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
        console.log(`Commit ${n} done`);
        makeCommits(n - 1); // Recursive call
      })
      .catch((err) => console.error("Error during git operation:", err));
  });
};

// Call the function correctly
makeCommits(50);


// const markCommit = (x, y) => {
//   const date = moment()
//     .subtract(1, "y")
//     .add(1, "d")
//     .add(x, "w")
//     .add(y, "d")
//     .format();

//   const data = {
//     date: date,
//   };

//   jsonfile.writeFile(path, data, () => {
//     simpleGit().add([path]).commit(date, { "--date": date }).push();
//   });
// };

// const makeCommits = (n) => {
//   if(n===0) return simpleGit().push();
//   const x = random.int(0, 54);
//   const y = random.int(0, 6);
//   const date = moment().subtract(1, "y").add(1, "d").add(x, "w").add(y, "d").format();

//   const data = {
//     date: date,
//   };
//   console.log(date);
//   jsonfile.writeFile(path, data, () => {
//     simpleGit().add([path]).commit(date, { "--date": date },makeCommits.bind(this,--n));
//   });
// };

// makeCommits(100);
