<script>
  import { Router, Link, Route } from "svelte-routing";
  import Home from "./Home.svelte";
  import About from "./About.svelte";
  import SignOut from "./SignOut.svelte";

  export let url = '';
</script>

<main>
  <Router {url}>
    <nav>
      <Link to="/home">Home</Link>
      <Link to="/about">About</Link>
      <Link to="/sign-out">Sign out</Link>
    </nav>
    <div>
      <Route path="/about" component={About} />
      <Route path="/home" component={Home} />
      <Route path="/sign-out" component={SignOut} />
    </div>
  </Router>
</main>

<style>
</style>
